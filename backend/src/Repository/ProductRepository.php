<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Product> */
final class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function findOneByExternalCode(string $externalCode): ?Product
    {
        return $this->findOneBy(['externalCode' => $externalCode]);
    }

    /** @return list<Product> */
   public function findAllForList(): array

    {

        $result = $this->createQueryBuilder('product')

            ->leftJoin('product.images', 'image')

            ->addSelect('image')

            ->orderBy('product.id', 'ASC')

            ->getQuery()

            ->getResult();

        /** @var list<Product> $result */

        return $result;

    }

   public function findForDetails(int $id): ?Product

    {

        $result = $this->createQueryBuilder('product')

            ->leftJoin('product.attributes', 'attribute')

            ->addSelect('attribute')

            ->leftJoin('product.images', 'image')

            ->addSelect('image')

            ->andWhere('product.id = :id')

            ->setParameter('id', $id)

            ->getQuery()

            ->getOneOrNullResult();

        /** @var Product|null $result */

        return $result;

    }
}
