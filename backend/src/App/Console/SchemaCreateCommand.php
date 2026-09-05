<?php

declare(strict_types=1);

namespace App\Console;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SchemaCreateCommand extends Command
{
    public function __construct(
        private EntityManager $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('schema:create')
            ->setDescription('Drop and recreate all tables (dev only)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<warning>Dropping old tables...</warning>');
        $conn = $this->em->getConnection();
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        $conn->executeStatement('DROP TABLE IF EXISTS product_images');
        $conn->executeStatement('DROP TABLE IF EXISTS product_attributes');
        $conn->executeStatement('DROP TABLE IF EXISTS products');
        $conn->executeStatement('DROP TABLE IF EXISTS import_tasks');
        $conn->executeStatement('DROP TABLE IF EXISTS users');
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 1');

        $output->writeln('Creating schema...');
        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->createSchema($metadata);
        $output->writeln('<info>Done.</info>');
        return 0;
    }
}
