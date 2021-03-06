<?php

namespace AppBundle\Sylius\Product;

use Ramsey\Uuid\Uuid;
use Sylius\Component\Product\Factory\ProductVariantFactoryInterface;
use Sylius\Component\Product\Model\ProductInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Sylius\Component\Product\Model\ProductVariantInterface;
use Sylius\Component\Product\Resolver\ProductVariantResolverInterface;

class LazyProductVariantResolver implements LazyProductVariantResolverInterface
{
    private $variantResolver;
    private $variantFactory;

    public function __construct(
        ProductVariantResolverInterface $variantResolver,
        ProductVariantFactoryInterface $variantFactory)
    {
        $this->variantResolver = $variantResolver;
        $this->variantFactory = $variantFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getVariant(ProductInterface $product): ?ProductVariantInterface
    {
        return $this->variantResolver->getVariant($product);
    }

    /**
     * {@inheritdoc}
     */
    public function getVariantForOptionValues(ProductInterface $product, array $optionValues): ?ProductVariantInterface
    {
        foreach ($product->getVariants() as $variant) {

            if (count($variant->getOptionValues()) !== count($optionValues)) {
                continue;
            }

            if ($this->matchOptions($variant, $optionValues)) {
                return $variant;
            }
        }

        // No variant found
        $variant = $this->variantFactory->createForProduct($product);
        $values = [];
        foreach ($optionValues as $optionValue) {
            $variant->addOptionValue($optionValue);
        }

        $variant->setName($product->getName());
        $variant->setCode(Uuid::uuid4()->toString());

        $defaultVariant = $this->variantResolver->getVariant($product);

        // Copy price & tax category from default variant
        $variant->setPrice($defaultVariant->getPrice());
        $variant->setTaxCategory($defaultVariant->getTaxCategory());

        return $variant;
    }

    private function matchOptions(ProductVariantInterface $variant, array $optionValues)
    {
        foreach ($optionValues as $optionValue) {
            if (!$variant->hasOptionValue($optionValue)) {
                return false;
            }
        }

        return true;
    }
}
