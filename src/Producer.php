<?php
declare(strict_types = 1);

namespace Innmind\AMQPBundle;

use Innmind\AMQP\Model\Basic\Message;

interface Producer
{
    public function __invoke(Message $message, string $routingKey = null): self;
}
