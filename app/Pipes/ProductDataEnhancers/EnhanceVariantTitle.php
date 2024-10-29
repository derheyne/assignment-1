<?php
declare(strict_types=1);

namespace App\Pipes\ProductDataEnhancers;

use App\Data\InternalProductData;
use App\Data\InternalVariantData;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class EnhanceVariantTitle
{
    protected const string VARIANT_TITLE_SEPARATOR = ' / ';

    /** @param  Collection<InternalProductData>  $normalisedProducts */
    public function handle(Collection $normalisedProducts, Closure $next)
    {
        foreach ($normalisedProducts as $product) {
            if (! $product->variants->contains(fn(InternalVariantData $variantData) => is_null($variantData->title))) {
                continue;
            }

            foreach ($product->variants as $variant) {
                if (! is_null($variant->title)) {
                    continue;
                }

                Log::info(__CLASS__.': Variant without title found. Attempting creation ...', [
                    'variantSku' => $variant->sku,
                ]);

                $variant->title = $this->buildTitleForVariant($variant, $product);

                if (! $variant->title) {
                    Log::warning(__CLASS__.': Unable to create title for variant.', [
                        'variantSku' => $variant->sku,
                    ]);
                } else {
                    Log::info(__CLASS__.': Created title for variant.', [
                        'variantSku' => $variant->sku,
                        'variantTitle' => $variant->title,
                    ]);
                }
            }
        }

        return $next($normalisedProducts);
    }

    protected function buildTitleForVariant(InternalVariantData $variant, InternalProductData $product): ?string
    {
        $parentTitle = $product->title;
        $sizeAttributeValue = $variant->attributes->firstWhere('type', EnhanceVariantSku::ATTRIBUTE_SIZE_TYPE);

        if (! $sizeAttributeValue) {
            return null;
        }

        return $parentTitle.self::VARIANT_TITLE_SEPARATOR.$sizeAttributeValue->value;
    }
}
