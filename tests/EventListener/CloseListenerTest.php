<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQPBundle\EventListener;

use Innmind\AMQPBundle\EventListener\CloseListener;
use Innmind\AMQP\Client;
use Symfony\Component\{
    EventDispatcher\EventSubscriberInterface,
    HttpKernel\KernelEvents,
    Console\ConsoleEvents
};
use PHPUnit\Framework\TestCase;

class CloseListenerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            EventSubscriberInterface::class,
            new CloseListener(
                $this->createMock(Client::class)
            )
        );
    }

    public function testSubscribedEvents()
    {
        $this->assertSame(
            [
                KernelEvents::TERMINATE => 'close',
                ConsoleEvents::TERMINATE => 'close',
            ],
            CloseListener::getSubscribedEvents()
        );
    }

    public function testClose()
    {
        $listener = new CloseListener(
            $client = $this->createMock(Client::class)
        );
        $client
            ->expects($this->once())
            ->method('close');

        $this->assertNull($listener->close());
    }
}
