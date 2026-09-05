<?php

declare(strict_types=1);

namespace App\Console;

use Doctrine\ORM\EntityManager;
use App\Entities\Product;
use App\Entities\ProductAttribute;
use App\Entities\ProductImage;
use App\Entities\User;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SeedCommand extends Command
{
    public function __construct(
        private EntityManager $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('db:seed')
            ->setDescription('Seed database with test data');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Seeding database...');

        // Create admin user
        $admin = new User();
        $admin->setEmail('admin@example.com');
        $admin->setPasswordHash(password_hash('password', PASSWORD_BCRYPT));
        $this->em->persist($admin);
        $this->em->flush();
        $output->writeln('  Created: admin@example.com / password');

        $products = [
            [
                'code' => 'WDG-001',
                'name' => 'Wireless Mouse',
                'description' => 'Ergonomic wireless mouse with USB receiver',
                'price' => 29.99,
                'purchase' => 12.50,
                'attrs' => ['Color' => 'Black', 'Connectivity' => 'Wireless', 'Weight' => '85g'],
                'images' => ['https://placehold.co/300x300?text=Mouse'],
            ],
            [
                'code' => 'WDG-002',
                'name' => 'Mechanical Keyboard',
                'description' => 'RGB mechanical keyboard with Blue switches',
                'price' => 79.99,
                'purchase' => 35.00,
                'attrs' => ['Color' => 'White', 'Switch Type' => 'Blue', 'Backlight' => 'RGB'],
                'images' => ['https://placehold.co/300x300?text=Keyboard'],
            ],
            [
                'code' => 'WDG-003',
                'name' => 'USB-C Hub',
                'description' => '7-in-1 USB-C hub with HDMI output',
                'price' => 45.00,
                'purchase' => 18.00,
                'attrs' => ['Ports' => '7', 'Output' => 'HDMI 4K', 'Material' => 'Aluminum'],
                'images' => ['https://placehold.co/300x300?text=Hub'],
            ],
            [
                'code' => 'WDG-004',
                'name' => 'Laptop Stand',
                'description' => 'Adjustable aluminum laptop stand',
                'price' => 35.00,
                'purchase' => 14.00,
                'attrs' => ['Material' => 'Aluminum', 'Adjustable' => 'Yes', 'Max Load' => '10kg'],
                'images' => ['https://placehold.co/300x300?text=Stand'],
            ],
            [
                'code' => 'WDG-005',
                'name' => 'Webcam 1080p',
                'description' => 'Full HD webcam with built-in microphone',
                'price' => 59.99,
                'purchase' => 25.00,
                'attrs' => ['Resolution' => '1080p', 'Microphone' => 'Built-in', 'FOV' => '90°'],
                'images' => ['https://placehold.co/300x300?text=Webcam'],
            ],
            [
                'code' => 'WDG-006',
                'name' => 'Monitor Light Bar',
                'description' => 'LED monitor light bar with remote control',
                'price' => 49.99,
                'purchase' => 20.00,
                'attrs' => ['Color Temperature' => '2700K-6500K', 'Power' => 'USB-C', 'CRI' => '95+'],
                'images' => ['https://placehold.co/300x300?text=LightBar'],
            ],
            [
                'code' => 'WDG-007',
                'name' => 'Desk Mat',
                'description' => 'Large extended desk mat, 900x400mm',
                'price' => 19.99,
                'purchase' => 6.00,
                'attrs' => ['Size' => '900x400mm', 'Material' => 'PU Leather', 'Color' => 'Dark Gray'],
                'images' => ['https://placehold.co/300x300?text=DeskMat'],
            ],
            [
                'code' => 'WDG-008',
                'name' => 'Cable Management Kit',
                'description' => 'Silicone cable clips and organizers',
                'price' => 12.99,
                'purchase' => 3.50,
                'attrs' => ['Pieces' => '12', 'Material' => 'Silicone', 'Adhesive' => '3M'],
                'images' => ['https://placehold.co/300x300?text=Cables'],
            ],
            [
                'code' => 'WDG-009',
                'name' => 'Wireless Charger',
                'description' => '15W fast wireless charger pad',
                'price' => 24.99,
                'purchase' => 9.00,
                'attrs' => ['Power' => '15W', 'Compatibility' => 'Qi', 'LED Indicator' => 'Yes'],
                'images' => ['https://placehold.co/300x300?text=Charger'],
            ],
            [
                'code' => 'WDG-010',
                'name' => 'Portable SSD 500GB',
                'description' => 'USB 3.2 portable SSD, 500GB',
                'price' => 69.99,
                'purchase' => 38.00,
                'attrs' => ['Capacity' => '500GB', 'Interface' => 'USB 3.2', 'Speed' => '1050MB/s'],
                'images' => ['https://placehold.co/300x300?text=SSD'],
            ],
        ];

        foreach ($products as $data) {
            $product = new Product();
            $product->setExternalCode($data['code']);
            $product->setName($data['name']);
            $product->setDescription($data['description']);
            $product->setPrice($data['price']);
            $product->setPurchasePrice($data['purchase']);
            $product->calculateDiscount();
            $this->em->persist($product);
            $this->em->flush();

            foreach ($data['attrs'] as $key => $value) {
                $attr = new ProductAttribute();
                $attr->setProduct($product);
                $attr->setKey($key);
                $attr->setValue($value);
                $this->em->persist($attr);
            }

            foreach ($data['images'] as $url) {
                $image = new ProductImage();
                $image->setProduct($product);
                $image->setUrl($url);
                $this->em->persist($image);
            }

            $this->em->flush();
            $output->writeln("  Created: {$data['name']}");
        }

        $output->writeln('<info>Seeding complete! 10 products created.</info>');
        return 0;
    }
}
