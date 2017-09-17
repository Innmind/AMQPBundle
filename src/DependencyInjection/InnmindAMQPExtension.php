<?php
declare(strict_types = 1);

namespace Innmind\AMQPBundle\DependencyInjection;

use Innmind\AMQPBundle\Producer\Producer;
use Symfony\Component\{
    HttpKernel\DependencyInjection\Extension,
    DependencyInjection\ContainerBuilder,
    DependencyInjection\Loader,
    DependencyInjection\Reference,
    DependencyInjection\Definition,
    Config\FileLocator
};

final class InnmindAMQPExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.yml');
        $config = $this->processConfiguration(
            new Configuration,
            $configs
        );

        $this
            ->configureConnection($config, $container)
            ->registerTranslators($config, $container)
            ->registerExchanges($config, $container)
            ->registerQueues($config, $container)
            ->registerBindings($config, $container)
            ->registerProducers($config, $container);
    }

    private function configureConnection(
        array $config,
        ContainerBuilder $container
    ): self {
        $container
            ->getDefinition('innmind.amqp.connection.default')
            ->replaceArgument(0, $config['server']['transport']['name'])
            ->replaceArgument(1, $config['server'])
            ->replaceArgument(3, $config['server']['timeout'])
            ->replaceArgument(4, new Reference($config['clock']));

        return $this;
    }

    private function registerTranslators(
        array $config,
        ContainerBuilder $container
    ): self {
        $definition = $container->getDefinition('innmind.amqp.argument_translator');

        foreach ($config['argument_translators'] as $translator) {
            $definition->addArgument(new Reference($translator));
        }

        return $this;
    }

    private function registerExchanges(
        array $config,
        ContainerBuilder $container
    ): self {
        $autoDeclare = $container->getDefinition('innmind.amqp.client.auto_declare');

        foreach ($config['exchanges'] as $name => $exchange) {
            $autoDeclare->addMethodCall(
                'declareExchange',
                [$name, $exchange['type'], $exchange['durable'], $exchange['arguments']]
            );
        }

        return $this;
    }

    private function registerQueues(
        array $config,
        ContainerBuilder $container
    ): self {
        $autoDeclare = $container->getDefinition('innmind.amqp.client.auto_declare');
        $consumers = $container->getDefinition('innmind.amqp.consumers');

        foreach ($config['queues'] as $name => $queue) {
            $autoDeclare->addMethodCall(
                'declareQueue',
                [$name, $queue['durable'], $queue['exclusive'], $queue['arguments']]
            );
            $consumers->addMethodCall(
                'add',
                [$name, new Reference($queue['consumer'])]
            );
        }

        return $this;
    }

    private function registerBindings(
        array $config,
        ContainerBuilder $container
    ): self {
        $autoDeclare = $container->getDefinition('innmind.amqp.client.auto_declare');

        foreach ($config['bindings'] as $binding) {
            $autoDeclare->addMethodCall(
                'declareBinding',
                [$binding['exchange'], $binding['queue'], $binding['routingKey'], $binding['arguments']]
            );
        }

        return $this;
    }

    private function registerProducers(
        array $config,
        ContainerBuilder $container
    ): self {
        foreach ($config['exchanges'] as $name => $_) {
            $container->setDefinition(
                'innmind.amqp.producer.'.$name,
                new Definition(
                    Producer::class,
                    [
                        new Reference('innmind.amqp.client'),
                        $name
                    ]
                )
            );
        }

        return $this;
    }
}
