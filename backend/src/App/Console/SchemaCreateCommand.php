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
        $platform = $conn->getDatabasePlatform();
        $isMySQL = $platform instanceof \Doctrine\DBAL\Platforms\MySQLPlatform
            || $platform instanceof \Doctrine\DBAL\Platforms\MariaDBPlatform;

        if ($isMySQL) {
            $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        }

        foreach (['product_images', 'product_attributes', 'products', 'import_tasks', 'users'] as $table) {
            $conn->executeStatement(sprintf('DROP TABLE IF EXISTS %s', $table));
        }

        if ($isMySQL) {
            $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
        }

        $output->writeln('Creating schema...');
        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->createSchema($metadata);
        $output->writeln('<info>Done.</info>');
        return 0;
    }
}
