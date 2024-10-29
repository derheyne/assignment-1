<?php
declare(strict_types=1);

namespace App\Pipes\ProductDataEnhancers;

use App\Data\InternalProductData;
use App\Data\InternalVariantData;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class RejectVariantsWithoutPrice
{
    /** @param  Collection<InternalProductData>  $normalisedProducts */
    public function handle(Collection $normalisedProducts, Closure $next)
    {
        foreach ($normalisedProducts as $product) {
            if (! $product->variants->contains(fn(InternalVariantData $variantData) => is_null($variantData->price))) {
                continue;
            }

            foreach ($product->variants as $index => $variant) {
                if (! is_null($variant->price)) {
                    continue;
                }

                Log::warning(__CLASS__.': Rejecting variant without price', [
                    'variantSku' => $variant->sku,
                ]);

                $product->variants = $product->variants->forget($index);
            }
        }

        return $next($normalisedProducts);
    }
}
