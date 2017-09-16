<?php
declare(strict_types = 1);

namespace Innmind\AMQPBundle;

use Innmind\Immutable\Map;

final class Consumers
{
    private $map;

    public function __construct()
    {
        $this->map = new Map('string', 'callable');
    }

    public function add(string $queue, callable $consumer): void
    {
        $this->map = $this->map->put($queue, $consumer);
    }

    public function contains(string $queue): bool
    {
        return $this->map->contains($queue);
    }

    public function get(string $queue): callable
    {
        return $this->map->get($queue);
    }
}
