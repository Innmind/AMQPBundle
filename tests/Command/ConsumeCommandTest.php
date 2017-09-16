<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQPBundle\Command;

use Innmind\AMQPBundle\{
    Command\ConsumeCommand,
    DependencyInjection\InnmindAMQPExtension
};
use Innmind\AMQP\{
    Model\Basic\Publish,
    Model\Basic\Message\Generic,
    Exception\Cancel
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

class ConsumeCommandTest extends TestCase
{
    public function testConsumeTwoMessages()
    {
        $extension = new InnmindAMQPExtension;
        $container = new ContainerBuilder;
        $container->setDefinition(
            'logger',
            new Definition(NullLogger::class)
        );
        $calls = 0;
        $container->set('foo', $consumer = function($message) use (&$calls): void {
            ++$calls;
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

        foreach (range(0, 3) as $i) {
            $container
                ->get('innmind.amqp.client')
                ->channel()
                ->basic()
                ->publish(
                    (new Publish(new Generic(new Str('foobar'))))->to('bundle_exchange'));
        }

        $command = new ConsumeCommand;

        $this->assertInstanceOf(ContainerAwareCommand::class, $command);

        $command->setContainer($container);
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'queue' => 'bundle_queue',
            'number' => '2',
        ));
        $this->assertSame(2, $calls);
    }

    public function testConsumeInfiniteMessages()
    {
        $extension = new InnmindAMQPExtension;
        $container = new ContainerBuilder;
        $container->setDefinition(
            'logger',
            new Definition(NullLogger::class)
        );
        $calls = 0;
        $container->set('foo', $consumer = function($message) use (&$calls): void {
            ++$calls;
            $this->assertSame('foobar', (string) $message->body());

            if ($calls === 4) {
                throw new Cancel;
            }
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

        foreach (range(0, 3) as $i) {
            $container
                ->get('innmind.amqp.client')
                ->channel()
                ->basic()
                ->publish(
                    (new Publish(new Generic(new Str('foobar'))))->to('bundle_exchange'));
        }

        $command = new ConsumeCommand;

        $this->assertInstanceOf(ContainerAwareCommand::class, $command);

        $command->setContainer($container);
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'queue' => 'bundle_queue',
        ));
        $this->assertSame(4, $calls);
    }
}
