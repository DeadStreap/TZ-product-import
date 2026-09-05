<?php

declare(strict_types=1);

namespace App\DTO;

class ProductFilter
{
    public function __construct(
        private ?string $name = null,
        private ?float $minPrice = null,
        private ?float $maxPrice = null,
    ) {
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getMinPrice(): ?float
    {
        return $this->minPrice;
    }

    public function getMaxPrice(): ?float
    {
        return $this->maxPrice;
    }

    public static function fromRequest(array $params): self
    {
        return new self(
            name: !empty($params['name']) ? (string) $params['name'] : null,
            minPrice: isset($params['minPrice']) && $params['minPrice'] !== '' ? (float) $params['minPrice'] : null,
            maxPrice: isset($params['maxPrice']) && $params['maxPrice'] !== '' ? (float) $params['maxPrice'] : null,
        );
    }
}
