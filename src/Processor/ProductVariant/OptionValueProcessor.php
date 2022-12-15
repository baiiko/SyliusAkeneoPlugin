<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\ProductVariant;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Synolia\SyliusAkeneoPlugin\Builder\ProductOptionValue\ProductOptionValueBuilderInterface;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactoryInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ProductGroup;
use Synolia\SyliusAkeneoPlugin\Entity\ProductGroupInterface;
use Synolia\SyliusAkeneoPlugin\Event\ProductOptionValue\AfterProcessingProductOptionValueEvent;
use Synolia\SyliusAkeneoPlugin\Event\ProductOptionValue\BeforeProcessingProductOptionValueEvent;
use Synolia\SyliusAkeneoPlugin\Exceptions\Builder\ProductOptionValue\ProductOptionValueBuilderNotFoundException;
use Synolia\SyliusAkeneoPlugin\Repository\ProductGroupRepository;
use Synolia\SyliusAkeneoPlugin\Transformer\ProductOptionValueDataTransformerInterface;
use Webmozart\Assert\Assert;

class OptionValueProcessor implements OptionValueProcessorInterface
{
    private RepositoryInterface $productOptionRepository;

    private RepositoryInterface $productOptionValueRepository;

    private ProductGroupRepository $productGroupRepository;

    private ProductOptionValueDataTransformerInterface $productOptionValueDataTransformer;

    private ClientFactoryInterface $clientFactory;

    private LoggerInterface $akeneoLogger;

    private EntityManagerInterface $entityManager;

    private ProductOptionValueBuilderInterface $productOptionValueBuilder;

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        RepositoryInterface $productOptionRepository,
        RepositoryInterface $productOptionValueRepository,
        ProductGroupRepository $productGroupRepository,
        ProductOptionValueDataTransformerInterface $productOptionValueDataTransformer,
        ClientFactoryInterface $clientFactory,
        LoggerInterface $akeneoLogger,
        EntityManagerInterface $entityManager,
        ProductOptionValueBuilderInterface $productOptionValueBuilder,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->productOptionRepository = $productOptionRepository;
        $this->productOptionValueRepository = $productOptionValueRepository;
        $this->productGroupRepository = $productGroupRepository;
        $this->productOptionValueDataTransformer = $productOptionValueDataTransformer;
        $this->clientFactory = $clientFactory;
        $this->akeneoLogger = $akeneoLogger;
        $this->entityManager = $entityManager;
        $this->productOptionValueBuilder = $productOptionValueBuilder;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function process(ProductVariantInterface $productVariant, array $resource): void
    {
        $productModel = $productVariant->getProduct();
        Assert::isInstanceOf($productModel, ProductInterface::class);

        $productGroup = $this->productGroupRepository->findOneBy(['model' => $resource['parent']]);

        if (!$productGroup instanceof ProductGroupInterface) {
            throw new \LogicException(\sprintf(
                'Could not find ProductGroup for model %s',
                $resource['parent'],
            ));
        }

        $familyVariantPayload = $this->clientFactory
            ->createFromApiCredentials()
            ->getFamilyVariantApi()
            ->get(
                $resource['family'],
                $productGroup->getFamilyVariant()
            )
        ;

        $variationAxes = [];
        foreach ($familyVariantPayload['variant_attribute_sets'] as $variantAttributeSet) {
            foreach ($variantAttributeSet['axes'] as $axe) {
                $variationAxes[] = $axe;
            }
        }

        foreach ($resource['values'] as $attributeCode => $values) {
            /*
             * Skip attributes that aren't variation axes.
             * Variation axes value will be created as option for the product
             */
            if (!\in_array($attributeCode, $variationAxes, true)) {
                continue;
            }

            /** @var ProductOptionInterface $productOption */
            $productOption = $this->productOptionRepository->findOneBy(['code' => $attributeCode]);

            //We cannot create the variant if the option does not exist
            if (!$productOption instanceof ProductOptionInterface) {
                $this->akeneoLogger->warning(
                    sprintf(
                        'Skipped ProductVariant "%s" creation because ProductOption "%s" does not exist.',
                        $productVariant->getCode(),
                        $attributeCode
                    )
                );

                continue;
            }

            if (!$productModel->hasOption($productOption)) {
                $productModel->addOption($productOption);
            }

            $this->setProductOptionValues($productVariant, $productOption, $values);
        }
    }

    private function setProductOptionValues(
        ProductVariantInterface $productVariant,
        ProductOptionInterface $productOption,
        array $attributeValues
    ): void {
        foreach ($attributeValues as $optionValue) {
            $this->eventDispatcher->dispatch(new BeforeProcessingProductOptionValueEvent($productOption, $attributeValues));

            $code = $this->getCode($productOption, $optionValue['data']);

            $productOptionValue = $this->productOptionValueRepository->findOneBy([
                'option' => $productOption,
                'code' => $code,
            ]);

            if (!$productOptionValue instanceof ProductOptionValueInterface) {
                try {
                    $productOptionValue = $this->productOptionValueBuilder->build($productOption, $attributeValues);
                    $this->entityManager->persist($productOptionValue);
                } catch (ProductOptionValueBuilderNotFoundException $e) {
                }
            }

            if (!$productOptionValue instanceof ProductOptionValueInterface) {
                $this->akeneoLogger->warning('Could not create ProductOptionValue for ProductVariant', [
                    'product_variant_code' => $productVariant->getCode(),
                    'attribute_values' => $attributeValues,
                ]);

                continue;
            }

            //Product variant already have this value
            if (!$productVariant->hasOptionValue($productOptionValue)) {
                $productVariant->addOptionValue($productOptionValue);
            }

            $this->eventDispatcher->dispatch(new AfterProcessingProductOptionValueEvent($productOption, $productOptionValue, $attributeValues));
        }
    }

    /**
     * @param array|string $data
     */
    private function getCode(ProductOptionInterface $productOption, $data): string
    {
        if (!\is_array($data)) {
            return $this->productOptionValueDataTransformer->transform($productOption, $data);
        }

        return $this->productOptionValueDataTransformer->transform($productOption, implode('_', $data));
    }

    public function support(ProductVariantInterface $productVariant, array $resource): bool
    {
        $productModel = $productVariant->getProduct();

        if (!$productModel instanceof ProductInterface) {
            return false;
        }

        $productGroup = $this->productGroupRepository->findOneBy(
            ['model' => $productModel->getCode()]
        );

        if (!$productGroup instanceof ProductGroup) {
            $this->akeneoLogger->warning(
                sprintf(
                    'Skipped product "%s" because model "%s" does not exist as group.',
                    $resource['identifier'],
                    $resource['parent'],
                )
            );

            return false;
        }

        //TODO: à changer
        $variationAxes = $productGroup->getVariationAxes();

        if (0 === \count($variationAxes)) {
            $this->akeneoLogger->warning(
                sprintf(
                    'Skipped product "%s" because group has no variation axis.',
                    $resource['identifier'],
                )
            );

            return false;
        }

        return true;
    }

    public static function getDefaultPriority(): int
    {
        return 900;
    }
}
