<?php
declare(strict_types=1);

namespace App\Pipes\ProductDataEnhancers;

use App\Data\InternalProductData;
use App\Data\InternalVariantData;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class EnhanceProductType
{
    protected const string DEFAULT_PRODUCT_TYPE = 'unknown';

    /** @param  Collection<InternalProductData>  $normalisedProducts */
    public function handle(Collection $normalisedProducts, Closure $next)
    {
        foreach ($normalisedProducts as $product) {
            if ($product->type) {
                continue;
            }

            Log::info(__CLASS__.': Product without type found', [
                'sku' => $product->sku,
            ]);

            $product->type = self::DEFAULT_PRODUCT_TYPE;
        }

        return $next($normalisedProducts);
    }
}
