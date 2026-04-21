<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\ImportFileException;
use App\Service\Import\ProductImportService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Import')]
final class ImportController extends AbstractController
{
    public function __construct(private readonly ProductImportService $productImportService)
    {
    }

    #[Route('/api/import/products', name: 'api_import_products', methods: ['POST'])]
    #[OA\Post(
        path: '/api/import/products',
        summary: 'Import products from an uploaded .xlsx file',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['file'],
                    properties: [
                        new OA\Property(property: 'file', type: 'string', format: 'binary'),
                    ],
                    type: 'object',
                ),
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Import statistics and row-level errors'),
            new OA\Response(response: 400, description: 'Invalid upload or import format'),
        ],
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile) {
            throw new ImportFileException('Upload field "file" is required.');
        }

        $result = $this->productImportService->import($file);

        return $this->json($result->toArray());
    }
}
