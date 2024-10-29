<?php
declare(strict_types=1);

namespace App\Pipes\ProductDataEnhancers;

use App\Data\InternalProductData;
use App\Data\InternalVariantData;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class EnhanceVariantSku
{
    public const string ATTRIBUTE_SIZE_TYPE = 'size';
    protected const string VARIANT_SKU_SEPARATOR = '-';

    /** @param  Collection<InternalProductData>  $normalisedProducts */
    public function handle(Collection $normalisedProducts, Closure $next)
    {
        foreach ($normalisedProducts as $product) {
            if (! $product->variants->contains(fn(InternalVariantData $variantData) => is_null($variantData->sku))) {
                continue;
            }

            foreach ($product->variants as $index => $variant) {
                if (! is_null($variant->sku)) {
                    continue;
                }

                Log::info(__CLASS__.': Variant without SKU found. Attempting creation ...', [
                    'parentSku' => $product->sku,
                    'variantIndex' => $index,
                    'variantTitle' => $variant->title,
                ]);

                $variant->sku = $this->buildSkuForVariant($variant, $product);

                if (! $variant->sku) {
                    Log::warning(__CLASS__.': Unable to create SKU for variant.', [
                        'parentSku' => $product->sku,
                        'variantIndex' => $index,
                        'variantTitle' => $variant->title,
                    ]);
                }
            }
        }

        return $next($normalisedProducts);
    }

    protected function buildSkuForVariant(InternalVariantData $variant, InternalProductData $product): ?string
    {
        $parentSku = $product->sku;
        $sizeAttributeValue = $variant->attributes->firstWhere('type', self::ATTRIBUTE_SIZE_TYPE);

        if (! $sizeAttributeValue) {
            return null;
        }

        return $parentSku.self::VARIANT_SKU_SEPARATOR.$sizeAttributeValue->value;
    }
}
