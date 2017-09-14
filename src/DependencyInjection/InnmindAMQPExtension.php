<?php
declare(strict_types = 1);

namespace Innmind\AMQPBundle\DependencyInjection;

use Symfony\Component\{
    HttpKernel\DependencyInjection\Extension,
    DependencyInjection\ContainerBuilder,
    DependencyInjection\Loader,
    DependencyInjection\Reference,
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

        $container
            ->getDefinition('innmind.amqp.connection.default')
            ->replaceArgument(1, $config['server'])
            ->replaceArgument(3, $config['server']['timeout'])
            ->replaceArgument(4, new Reference($config['clock']));

        $definition = $container->getDefinition('innmind.amqp.argument_translator');

        foreach ($config['argument_translators'] as $translator) {
            $definition->addArgument(new Reference($translator));
        }
    }
}
