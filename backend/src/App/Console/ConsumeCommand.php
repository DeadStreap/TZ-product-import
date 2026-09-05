<?php

declare(strict_types=1);

namespace App\Console;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpTransportFactory;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Worker;
use App\Messages\ImportProductsHandler;
use App\Messages\ImportProductsMessage;
use App\Services\ImportService;
use App\Repositories\ProductRepository;
use App\Repositories\ProductAttributeRepository;
use App\Repositories\ProductImageRepository;
use App\Repositories\ImportTaskRepository;
use App\Services\ImageDownloadService;

class ConsumeCommand extends Command
{
    public function __construct(
        private EntityManager $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('messenger:consume')
            ->setDescription('Consume messages from AMQP transport')
            ->addArgument('transport', InputArgument::OPTIONAL, 'Transport name (ignored, uses MESSENGER_TRANSPORT_DSN)')
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Limit number of messages to handle', 0)
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
        $amqpFactory = new AmqpTransportFactory(null, $serializer);
        $transport = $amqpFactory->createTransport($transportDsn, [], $serializer);

        $handler = new HandlersLocator([
            ImportProductsMessage::class => [
                new ImportProductsHandler(
                    new ImportService(
                        $this->em,
                        new ProductRepository($this->em),
                        new ProductAttributeRepository($this->em),
                        new ProductImageRepository($this->em),
                        new ImageDownloadService($_ENV['UPLOAD_DIR'] ?? '/var/www/uploads'),
                    ),
                    new ImportTaskRepository($this->em),
                    $this->em,
                ),
            ],
        ]);

        $bus = new MessageBus([
            new HandleMessageMiddleware($handler),
        ]);

        $receiver = $transport->getReceiver();

        $output->writeln('Consuming messages... Press Ctrl+C to stop.');

        $worker = new Worker(
            ['async' => $receiver],
            $bus,
        );

        $worker->run([
            'limit' => (int) $input->getOption('limit'),
            'time_limit' => (int) $input->getOption('time-limit'),
        ]);

        return Command::SUCCESS;
    }
}
