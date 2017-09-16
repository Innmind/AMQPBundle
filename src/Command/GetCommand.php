<?php
declare(strict_types = 1);

namespace Innmind\AMQPBundle\Command;

use Innmind\AMQP\Model\Basic\Get;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\{
    Input\InputInterface,
    Input\InputArgument,
    Output\OutputInterface
};

final class GetCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('innmind:amqp:get')
            ->setDescription('Will process a single message from the given queue')
            ->addArgument('queue', InputArgument::REQUIRED);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $queue = $input->getArgument('queue');
        $consume = $this
            ->getContainer()
            ->get('innmind.amqp.consumers')
            ->get($queue);

        $this
            ->getContainer()
            ->get('innmind.amqp.client')
            ->channel()
            ->basic()
            ->get(new Get($queue))($consume);
    }
}
