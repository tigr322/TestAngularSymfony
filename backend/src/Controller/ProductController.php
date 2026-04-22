<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Service\Product\ProductCrudService;
use App\Service\Product\ProductResponseFactory;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Products')]
final class ProductController extends AbstractController
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly ProductResponseFactory $responseFactory,
        private readonly ProductCrudService $productCrudService,
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

    #[Route('/api/products', name: 'api_products_create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/products',
        summary: 'Create a product',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['externalCode', 'name', 'price'],
                properties: [
                    new OA\Property(property: 'externalCode', type: 'string', example: 'external-1'),
                    new OA\Property(property: 'name', type: 'string', example: 'Imported product'),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(property: 'price', type: 'string', example: '1200.00'),
                    new OA\Property(property: 'purchasePrice', type: 'string', nullable: true, example: '800.00'),
                    new OA\Property(
                        property: 'attributes',
                        type: 'object',
                        nullable: true,
                        additionalProperties: new OA\AdditionalProperties(type: 'string'),
                    ),
                ],
                type: 'object',
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Product created'),
            new OA\Response(response: 400, description: 'Validation error'),
            new OA\Response(response: 409, description: 'Duplicate externalCode'),
        ],
    )]
    public function create(Request $request): JsonResponse
    {
        $product = $this->productCrudService->create($this->jsonPayload($request));

        return $this->json($this->responseFactory->details($product), Response::HTTP_CREATED);
    }

    #[Route('/api/products/{id<\d+>}', name: 'api_products_details', methods: ['GET'])]
    #[OA\Get(
        path: '/api/products/{id}',
        summary: 'Read one product with attributes and images',
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

    #[Route('/api/products/{id<\d+>}', name: 'api_products_update', methods: ['PUT'])]
    #[OA\Put(
        path: '/api/products/{id}',
        summary: 'Update a product',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['externalCode', 'name', 'price'],
                properties: [
                    new OA\Property(property: 'externalCode', type: 'string', example: 'external-1'),
                    new OA\Property(property: 'name', type: 'string', example: 'Updated product'),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(property: 'price', type: 'string', example: '1500.00'),
                    new OA\Property(property: 'purchasePrice', type: 'string', nullable: true, example: '1000.00'),
                    new OA\Property(
                        property: 'attributes',
                        type: 'object',
                        nullable: true,
                        additionalProperties: new OA\AdditionalProperties(type: 'string'),
                    ),
                ],
                type: 'object',
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Product updated'),
            new OA\Response(response: 400, description: 'Validation error'),
            new OA\Response(response: 404, description: 'Product not found'),
            new OA\Response(response: 409, description: 'Duplicate externalCode'),
        ],
    )]
    public function update(int $id, Request $request): JsonResponse
    {
        $product = $this->productRepository->findForDetails($id);
        if ($product === null) {
            throw new NotFoundHttpException('Product not found.');
        }

        $product = $this->productCrudService->update($product, $this->jsonPayload($request));

        return $this->json($this->responseFactory->details($product));
    }

    #[Route('/api/products/{id<\d+>}', name: 'api_products_delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/products/{id}',
        summary: 'Delete a product',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Product deleted'),
            new OA\Response(response: 404, description: 'Product not found'),
        ],
    )]
    public function delete(int $id): Response
    {
        $product = $this->productRepository->find($id);
        if ($product === null) {
            throw new NotFoundHttpException('Product not found.');
        }

        $this->productCrudService->delete($product);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
/**
 * @return array<string, mixed>
 */
private function jsonPayload(Request $request): array
{
    if (trim($request->getContent()) === '') {
        throw new BadRequestHttpException('JSON request body is required.');
    }

    try {
        /** @var array<string, mixed> $data */
        $data = $request->toArray();

        return $data;
    } catch (JsonException) {
        throw new BadRequestHttpException('Invalid JSON request body.');
    }
}
}
