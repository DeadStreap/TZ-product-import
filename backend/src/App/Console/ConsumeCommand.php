<?php

declare(strict_types=1);

namespace App\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpTransportFactory;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Worker;

class ConsumeCommand extends Command
{
    public function __construct(
        private MessageBusInterface $bus,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('messenger:consume')
            ->setDescription('Consume messages from AMQP transport')
            ->addOption('time-limit', 't', InputOption::VALUE_REQUIRED, 'Time limit in seconds', 0);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $transportDsn = $_ENV['MESSENGER_TRANSPORT_DSN'] ?? null;

        if (!$transportDsn) {
            $output->writeln('<error>MESSENGER_TRANSPORT_DSN is not configured.</error>');
            return Command::FAILURE;
        }

        if (!extension_loaded('amqp')) {
            $output->writeln('<error>ext-amqp is not installed.</error>');
            return Command::FAILURE;
        }

        $serializer = new PhpSerializer();
        $amqpFactory = new AmqpTransportFactory();
        $transport = $amqpFactory->createTransport($transportDsn, [], $serializer);

        $output->writeln('Consuming messages... Press Ctrl+C to stop.');

        $worker = new Worker(
            ['async' => $transport],
            $this->bus,
        );

        $worker->run([
            'time_limit' => (int) $input->getOption('time-limit') ?: null,
        ]);

        return Command::SUCCESS;
    }
}
