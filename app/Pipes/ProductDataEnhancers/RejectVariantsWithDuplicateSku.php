<?php
declare(strict_types=1);

namespace App\Pipes\ProductDataEnhancers;

use App\Data\InternalProductData;
use App\Data\InternalVariantData;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class RejectVariantsWithDuplicateSku
{
    /** @param  Collection<InternalProductData>  $normalisedProducts */
    public function handle(Collection $normalisedProducts, Closure $next)
    {
        foreach ($normalisedProducts as $product) {
            $duplicates = $product->variants->duplicates('sku');
            if ($duplicates->isEmpty()) {
                continue;
            }

            foreach ($duplicates as $duplicateSku) {
                $duplicateVariantsForSku = $product->variants->where('sku', $duplicateSku);
                $maxPrice = $duplicateVariantsForSku->max('price');

                // reject all but the highest priced variant
                $rejectedVariants = $product->variants->reject(
                    fn(InternalVariantData $variant) => $variant->sku === $duplicateSku && $variant->price < $maxPrice,
                );

                Log::warning(__CLASS__.': Rejecting lower-priced variants with duplicate SKU', [
                    'variantSku' => $duplicateSku,
                    'highestPrice' => $maxPrice,
                    'totalVariantCount' => $product->variants->count(),
                    'rejectedVariantCount' => $rejectedVariants->count(),
                ]);

                $product->variants = $rejectedVariants;
            }
        }

        return $next($normalisedProducts);
    }
}
