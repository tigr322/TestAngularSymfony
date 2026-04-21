<?php

declare(strict_types=1);

namespace App\Service\Import;

use App\DTO\ImportResult;
use App\Exception\ImportFileException;
use App\Service\Product\ProductSynchronizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class ProductImportService
{
    public function __construct(
        private XlsxProductParser $parser,
        private ProductSynchronizer $productSynchronizer,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function import(UploadedFile $file): ImportResult
    {
        if ($file->getClientOriginalExtension() !== 'xlsx') {
            throw new ImportFileException('Only .xlsx files are supported.');
        }

        $parsedRows = $this->parser->parse($file->getPathname());
        $result = new ImportResult();

        for ($i = 0; $i < $parsedRows->skipped; ++$i) {
            $result->incrementSkipped();
        }

        foreach ($parsedRows->errors as $error) {
            $result->addError($error);
        }

        foreach ($parsedRows->rows as $row) {
            $syncResult = $this->productSynchronizer->sync($row);

            if ($syncResult->created) {
                $result->incrementCreated();
            } else {
                $result->incrementUpdated();
            }

            foreach ($syncResult->errors as $error) {
                $result->addError($error);
            }
        }

        $this->entityManager->flush();

        return $result;
    }
}
