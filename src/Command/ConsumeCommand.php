<?php
declare(strict_types = 1);

namespace Innmind\AMQPBundle\Command;

use Innmind\AMQP\Model\Basic\Consume;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\{
    Input\InputInterface,
    Input\InputArgument,
    Output\OutputInterface
};

final class ConsumeCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('innmind:amqp:consume')
            ->setDescription('Will process messages from the given queue')
            ->addArgument('queue', InputArgument::REQUIRED)
            ->addArgument('number', InputArgument::OPTIONAL, 'The number of messages to process');
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

        $consumer = $this
            ->getContainer()
            ->get('innmind.amqp.client')
            ->channel()
            ->basic()
            ->consume(new Consume($queue));

        if ($input->getArgument('number')) {
            $consumer->take((int) $input->getArgument('number'));
        }

        $consumer->foreach($consume);
    }
}
