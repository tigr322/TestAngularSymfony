<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Service\Product\ProductResponseFactory;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Products')]
final class ProductController extends AbstractController
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly ProductResponseFactory $responseFactory,
    ) {
    }

    #[Route('/api/products', name: 'api_products_list', methods: ['GET'])]
    #[OA\Get(
        path: '/api/products',
        summary: 'List imported products',
        responses: [
            new OA\Response(response: 200, description: 'Product list'),
        ],
    )]
    public function list(): JsonResponse
    {
        $products = $this->productRepository->findAllForList();

        return $this->json([
            'items' => array_map(
                fn ($product): array => $this->responseFactory->listItem($product),
                $products,
            ),
        ]);
    }

    #[Route('/api/products/{id<\d+>}', name: 'api_products_details', methods: ['GET'])]
    #[OA\Get(
        path: '/api/products/{id}',
        summary: 'Show one imported product with attributes and images',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Product details'),
            new OA\Response(response: 404, description: 'Product not found'),
        ],
    )]
    public function details(int $id): JsonResponse
    {
        $product = $this->productRepository->findForDetails($id);
        if ($product === null) {
            throw new NotFoundHttpException('Product not found.');
        }

        return $this->json($this->responseFactory->details($product));
    }
}
