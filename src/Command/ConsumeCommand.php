<?php
declare(strict_types = 1);

namespace Innmind\AMQPBundle\Command;

use Innmind\AMQP\Model\Basic\{
    Consume,
    Qos
};
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
            ->addArgument('number', InputArgument::OPTIONAL, 'The number of messages to process')
            ->addArgument('prefetch', InputArgument::OPTIONAL, 'The number of messages to prefetch');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $queue = $input->getArgument('queue');
        $number = $input->getArgument('number');
        $prefetch = $input->getArgument('prefetch');
        $consume = $this
            ->getContainer()
            ->get('innmind.amqp.consumers')
            ->get($queue);

        $basic = $this
            ->getContainer()
            ->get('innmind.amqp.client')
            ->channel()
            ->basic();

        if (!is_null($number) || !is_null($prefetch)) {
            $basic->qos(new Qos(0, (int) ($prefetch ?? $number)));
        }

        $consumer = $basic->consume(new Consume($queue));

        if (!is_null($number)) {
            $consumer->take((int) $number);
        }

        $consumer->foreach($consume);
    }
}
