<?php
declare(strict_types = 1);

namespace Innmind\AMQPBundle\Command;

use Innmind\AMQP\{
    Model\Queue\Purge,
    Exception\UnexpectedFrame
};
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\{
    Input\InputInterface,
    Input\InputArgument,
    Output\OutputInterface
};

final class PurgeCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('innmind:amqp:purge')
            ->setDescription('Will delete all messages for the given queue')
            ->addArgument('queue', InputArgument::REQUIRED);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $queue = $input->getArgument('queue');
        try {
            $this
                ->getContainer()
                ->get('innmind.amqp.client')
                ->channel()
                ->queue()
                ->purge(new Purge($queue));
        } catch (UnexpectedFrame $e) {
            $output->writeln(sprintf(
                '<error>Purging <bg=green>%s</> failed</>',
                $queue
            ));

            return 1;
        }
    }
}
