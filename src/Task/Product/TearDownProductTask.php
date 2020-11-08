<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Product;

use Doctrine\ORM\EntityManagerInterface;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductPayload;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class TearDownProductTask implements AkeneoTaskInterface
{
    /** @var \Doctrine\ORM\EntityManagerInterface */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $exists = $this->entityManager->getConnection()->getSchemaManager()->tablesExist([ProductPayload::TEMP_AKENEO_TABLE_NAME]);

        if ($exists) {
            $this->entityManager->getConnection()->getSchemaManager()->dropTable(ProductPayload::TEMP_AKENEO_TABLE_NAME);
        }

        return $payload;
    }
}
