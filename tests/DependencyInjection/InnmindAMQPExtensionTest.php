<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQPBundle\DependencyInjection;

use Innmind\AMQPBundle\DependencyInjection\InnmindAMQPExtension;
use Innmind\AMQP\{
    Client,
    Model\Exchange\Declaration as Exchange,
    Model\Exchange\Type,
    Model\Queue\Declaration as Queue,
    Model\Queue\DeclareOk
};
use Symfony\Component\DependencyInjection\{
    ContainerBuilder,
    Definition
};
use Psr\Log\NullLogger;
use PHPUnit\Framework\TestCase;

class InnmindAMQPExtensionTest extends TestCase
{
    public function testLoad()
    {
        $extension = new InnmindAMQPExtension;
        $container = new ContainerBuilder;
        $container->setDefinition(
            'logger',
            new Definition(NullLogger::class)
        );
        $container->set('foo', $consumer = function(){});

        $this->assertNull(
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
            )
        );
        $container->compile();

        $this->assertInstanceOf(
            Client::class,
            $container->get('innmind.amqp.client')
        );
        $client = $container->get('innmind.amqp.client');
        $this->assertFalse($client->closed());
        $this->assertSame(
            $client->channel()->exchange(),
            $client
                ->channel()
                ->exchange()
                ->declare(
                    Exchange::passive('bundle_exchange', Type::direct())
                )
        );
        $this->assertInstanceOf(
            DeclareOk::class,
            $client
                ->channel()
                ->queue()
                ->declare(
                    Queue::passive('bundle_queue')
                )
        );
        $this->assertTrue(
            $container->get('innmind.amqp.consumers')->contains('bundle_queue')
        );
        $this->assertSame(
            $consumer,
            $container->get('innmind.amqp.consumers')->get('bundle_queue')
        );
    }
}
