<?php

declare(strict_types=1);

namespace App\Controllers;

use App\DTO\ProductFilter;
use App\Repositories\ProductRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ProductController
{
    public function __construct(private ProductRepository $productRepo) {}

    public function index(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();

        $page = max(1, (int) ($params['page'] ?? 1));
        $limit = max(1, min(100, (int) ($params['limit'] ?? 20)));

        $filter = ProductFilter::fromRequest($params);
        $result = $this->productRepo->findByFilter($filter, $page, $limit);

        $items = array_map(
            fn($p): array => $p->toListItemArray(),
            $result['items']
        );

        $response->getBody()->write(json_encode([
            'items' => $items,
            'total' => $result['total'],
            'page' => $result['page'],
            'limit' => $result['limit'],
            'total_pages' => $result['total_pages'],
        ]));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $product = $this->productRepo->find((int) $args['id']);

        if ($product === null) {
            $response->getBody()->write(json_encode(['error' => 'Product not found']));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        $response->getBody()->write(json_encode($product->toArray()));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}
