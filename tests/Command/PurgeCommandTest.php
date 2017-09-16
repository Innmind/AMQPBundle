<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQPBundle\Command;

use Innmind\AMQPBundle\{
    Command\PurgeCommand,
    DependencyInjection\InnmindAMQPExtension
};
use Symfony\Component\{
    DependencyInjection\ContainerBuilder,
    DependencyInjection\Definition,
    Console\Tester\CommandTester
};
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Psr\Log\NullLogger;
use PHPUnit\Framework\TestCase;

class PurgeCommandTest extends TestCase
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
        $container->set('foo', $consumer = function() use (&$called): void {
            $called = true;
        });

        $extension->load(
            [[
                'queues' => [
                    'bundle_queue' => [
                        'durable' => false,
                        'consumer' => 'foo',
                    ],
                ],
            ]],
            $container
        );
        $container->compile();

        $command = new PurgeCommand;

        $this->assertInstanceOf(ContainerAwareCommand::class, $command);

        $command->setContainer($container);
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'queue' => 'bundle_queue',
        ));
        $this->assertFalse($called);
        $container
            ->get('innmind.amqp.client')
            ->close();
    }

    public function testFailPurging()
    {
        $extension = new InnmindAMQPExtension;
        $container = new ContainerBuilder;
        $container->setDefinition(
            'logger',
            new Definition(NullLogger::class)
        );
        $called = false;
        $container->set('foo', $consumer = function() use (&$called): void {
            $called = true;
        });

        $extension->load(
            [],
            $container
        );
        $container->compile();

        $command = new PurgeCommand;

        $this->assertInstanceOf(ContainerAwareCommand::class, $command);

        $command->setContainer($container);
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'queue' => 'unknown',
        ));
        $this->assertFalse($called);
        $this->assertSame(1, $commandTester->getStatusCode());
        $this->assertContains(
            'Purging unknown failed',
            $commandTester->getDisplay()
        );
        $container
            ->get('innmind.amqp.client')
            ->close();
    }
}
