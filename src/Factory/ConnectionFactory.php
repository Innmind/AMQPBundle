<?php
declare(strict_types = 1);

namespace Innmind\AMQPBundle\Factory;

use Innmind\AMQP\Transport\{
    Connection\Connection,
    Protocol
};
use Innmind\Socket\Internet\Transport;
use Innmind\TimeContinuum\{
    TimeContinuumInterface,
    ElapsedPeriod
};
use Innmind\Url\{
    Url,
    NullScheme,
    Authority,
    Authority\UserInformation,
    Authority\UserInformation\User,
    Authority\UserInformation\Password,
    Authority\Host,
    Authority\Port,
    Path,
    NullQuery,
    NullFragment
};

final class ConnectionFactory
{
    public static function make(
        string $transport,
        array $server,
        Protocol $protocol,
        int $timeout,
        TimeContinuumInterface $clock
    ): Connection {
        $transport = Transport::$transport();

        foreach ($server['transport']['options'] as $key => $value) {
            $transport = $transport->withOption($key, $value);
        }

        return new Connection(
            $transport,
            new Url(
                new NullScheme,
                new Authority(
                    new UserInformation(
                        new User($server['user']),
                        new Password($server['password'])
                    ),
                    new Host($server['host']),
                    new Port($server['port'])
                ),
                new Path($server['vhost']),
                new NullQuery,
                new NullFragment
            ),
            $protocol,
            new ElapsedPeriod($timeout),
            $clock
        );
    }
}
