<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQPBundle\DependencyInjection;

use Innmind\AMQPBundle\DependencyInjection\InnmindAMQPExtension;
use Innmind\AMQP\Client;
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

        $this->assertNull(
            $extension->load(
                [],
                $container
            )
        );
        $container->compile();

        $this->assertInstanceOf(
            Client::class,
            $container->get('innmind.amqp.client')
        );
        $this->assertFalse($container->get('innmind.amqp.client')->closed());
    }
}
