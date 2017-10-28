<?php
declare(strict_types = 1);

namespace Innmind\AMQPBundle\EventListener;

use Innmind\AMQP\Client;
use Symfony\Component\{
    EventDispatcher\EventSubscriberInterface,
    HttpKernel\KernelEvents,
    Console\ConsoleEvents
};

final class CloseListener implements EventSubscriberInterface
{
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::TERMINATE => 'close',
            ConsoleEvents::TERMINATE => 'close',
        ];
    }

    public function close(): void
    {
        $this->client->close();
    }
}
