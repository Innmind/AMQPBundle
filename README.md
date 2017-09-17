# AMQPBundle

| `master` | `develop` |
|----------|-----------|
| [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Innmind/AMQPBundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Innmind/AMQPBundle/?branch=master) | [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Innmind/AMQPBundle/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/AMQPBundle/?branch=develop) |
| [![Code Coverage](https://scrutinizer-ci.com/g/Innmind/AMQPBundle/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/Innmind/AMQPBundle/?branch=master) | [![Code Coverage](https://scrutinizer-ci.com/g/Innmind/AMQPBundle/badges/coverage.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/AMQPBundle/?branch=develop) |
| [![Build Status](https://scrutinizer-ci.com/g/Innmind/AMQPBundle/badges/build.png?b=master)](https://scrutinizer-ci.com/g/Innmind/AMQPBundle/build-status/master) | [![Build Status](https://scrutinizer-ci.com/g/Innmind/AMQPBundle/badges/build.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/AMQPBundle/build-status/develop) |

## Installation

```sh
composer require innmind/amqp-bundle
```

Enable the bundle by adding the following line in your `AppKernel.php` of your project:

```php
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...
            new Innmind\AMQPBundle\InnmindAMQPBundle,
        );
        // ...
    }
    // ...
}
```

Then you need to declare the exchanges and queues you want to use:

```yml
innmind_amqp:
    server:
        host: localhost # default
        port: 5672 # default
        user: guest # default
        password: guest # default
        vhost: / # default
    exchanges:
        urls:
            type: direct
    queues:
        crawler:
            consumer: my_consumer_service_id
    bindings:
        -
            exchange: urls
            queue: crawler
```

Upon usage this will automatically create the exchange, the queue and the binding between the two in the AMQP server.

## Usage

In order to publish a new message you can simply do:

```php
use Innmind\AMQP\Model\Basic\Message\Generic;
use Innmind\Immutable\Str;

$container->get('innmind.amqp.producer.urls')(new Generic(new Str('http://example.com/')));
```

This will publish a message with the payload `http://example.com/` to the exchange `urls`.

Then to consume messages you can either do it by code using the AMQP client service `innmind.amqp.client` or via running the command `innmind:amqp:get` or `innmind:amqp:consume`. Both commands take the queue name as first argument (in our case it would be `crawler`); `innmind:amqp:consume` can take a second argument to declare the maximum number of messages you want to the command to consume.

Examples:
```sh
bin/console innmind:amqp:get crawler # process 1 message
#or
bin/console innmind:amqp:consume crawler # runs forever
#or
bin/console innmind:amqp:consume crawler 42 # process 42 messages
```
