<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260901 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create products, product_attributes, product_images, and import_tasks tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE products (
            id INT AUTO_INCREMENT NOT NULL,
            external_code VARCHAR(255) NOT NULL,
            name VARCHAR(500) NOT NULL,
            description TEXT DEFAULT NULL,
            price NUMERIC(10, 2) NOT NULL,
            purchase_price NUMERIC(10, 2) DEFAULT NULL,
            discount DOUBLE PRECISION DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE INDEX UNIQ_D34A04AD97AE0266 (external_code),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE product_attributes (
            id INT AUTO_INCREMENT NOT NULL,
            product_id INT NOT NULL,
            attr_key VARCHAR(255) NOT NULL,
            attr_value TEXT DEFAULT NULL,
            INDEX IDX_attributes_product_id (product_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE product_images (
            id INT AUTO_INCREMENT NOT NULL,
            product_id INT NOT NULL,
            url VARCHAR(1000) NOT NULL,
            path VARCHAR(500) DEFAULT NULL,
            INDEX IDX_images_product_id (product_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE import_tasks (
            id INT AUTO_INCREMENT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT \'pending\',
            result TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE product_attributes ADD CONSTRAINT FK_attr_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_images ADD CONSTRAINT FK_img_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product_images DROP FOREIGN KEY FK_img_product');
        $this->addSql('ALTER TABLE product_attributes DROP FOREIGN KEY FK_attr_product');
        $this->addSql('DROP TABLE product_images');
        $this->addSql('DROP TABLE product_attributes');
        $this->addSql('DROP TABLE import_tasks');
        $this->addSql('DROP TABLE products');
    }
}
