<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQPBundle\Command;

use Innmind\AMQPBundle\{
    Command\GetCommand,
    DependencyInjection\InnmindAMQPExtension
};
use Innmind\AMQP\Model\Basic\{
    Publish,
    Message\Generic
};
use Innmind\Immutable\Str;
use Symfony\Component\{
    DependencyInjection\ContainerBuilder,
    DependencyInjection\Definition,
    Console\Tester\CommandTester
};
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Psr\Log\NullLogger;
use PHPUnit\Framework\TestCase;

class GetCommandTest extends TestCase
{
    public function testExecution()
    {
        $extension = new InnmindAMQPExtension;
        $container = new ContainerBuilder;
        $container->setDefinition(
            'logger',
            new Definition(NullLogger::class)
        );
        $called = false;
        $container->set('foo', $consumer = function($message) use (&$called): void {
            $called = true;
            $this->assertSame('foobar', (string) $message->body());
        });

        $extension->load(
            [[
                'exchanges' => [
                    'bundle_exchange' => [
                        'type' => 'direct',
                        'durable' => false,
                    ],
                ],
                'queues' => [
                    'bundle_queue' => [
                        'durable' => false,
                        'consumer' => 'foo',
                    ],
                ],
                'bindings' => [[
                    'exchange' => 'bundle_exchange',
                    'queue' => 'bundle_queue',
                ]],
            ]],
            $container
        );
        $container->compile();

        $container
            ->get('innmind.amqp.client')
            ->channel()
            ->basic()
            ->publish(
                (new Publish(new Generic(new Str('foobar'))))->to('bundle_exchange'));

        $command = new GetCommand;

        $this->assertInstanceOf(ContainerAwareCommand::class, $command);

        $command->setContainer($container);
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'queue' => 'bundle_queue',
        ));
        $this->assertTrue($called);
        $container
            ->get('innmind.amqp.client')
            ->close();
    }
}
