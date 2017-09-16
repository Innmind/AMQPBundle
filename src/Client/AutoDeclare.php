<?php
declare(strict_types = 1);

namespace Innmind\AMQPBundle\Client;

use Innmind\AMQP\{
    Client,
    Client\Channel,
    Model\Exchange\Declaration as Exchange,
    Model\Exchange\Type,
    Model\Queue\Declaration as Queue,
    Model\Queue\Binding
};
use Innmind\Immutable\Set;

final class AutoDeclare implements Client
{
    private $client;
    private $exchanges;
    private $queues;
    private $bindings;
    private $declared = false;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->exchanges = new Set(Exchange::class);
        $this->queues = new Set(Queue::class);
        $this->bindings = new Set(Binding::class);
    }

    public function declareExchange(string $name, string $type, bool $durable, array $arguments): void
    {
        $constructor = $durable ? 'durable' : 'temporary';
        $exchange = Exchange::$constructor($name, Type::$type());

        foreach ($arguments as $key => $value) {
            $exchange = $exchange->withArgument($key, $value);
        }

        $this->exchanges = $this->exchanges->add($exchange);
    }

    public function declareQueue(string $name, bool $durable, bool $exclusive, array $arguments): void
    {
        $constructor = $durable ? 'durable' : 'temporary';
        $queue = Queue::$constructor()->withName($name);

        if ($exclusive) {
            $queue = $queue->exclusive();
        }

        foreach ($arguments as $key => $value) {
            $queue = $queue->withArgument($key, $value);
        }

        $this->queues = $this->queues->add($queue);
    }

    public function declareBinding(string $exchange, string $queue, string $routingKey, array $arguments): void
    {
        $binding = new Binding($exchange, $queue, $routingKey);

        foreach ($arguments as $key => $value) {
            $binding = $binding->withArgument($key, $value);
        }

        $this->bindings = $this->bindings->add($binding);
    }

    public function channel(): Channel
    {
        $channel = $this->client->channel();
        $this->declareThrough($channel);

        return $channel;
    }

    public function closed(): bool
    {
        return $this->client->closed();
    }

    public function close(): void
    {
        $this->client->close();
        $this->declared = true;
    }

    private function declareThrough(Channel $channel): void
    {
        if ($this->declared) {
            return;
        }

        $this->exchanges->foreach(static function(Exchange $command) use ($channel): void {
            $channel->exchange()->declare($command);
        });
        $this->queues->foreach(static function(Queue $command) use ($channel): void {
            $channel->queue()->declare($command);
        });
        $this->bindings->foreach(static function(Binding $command) use ($channel): void {
            $channel->queue()->bind($command);
        });
    }
}
