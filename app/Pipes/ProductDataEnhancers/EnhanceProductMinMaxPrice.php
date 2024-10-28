<?php
declare(strict_types=1);

namespace App\Pipes\ProductDataEnhancers;

use App\Data\InternalProductData;
use Closure;
use Illuminate\Support\Collection;

class EnhanceProductMinMaxPrice
{
    /** @param  Collection<InternalProductData>  $normalisedProducts */
    public function handle(Collection $normalisedProducts, Closure $next)
    {
        foreach ($normalisedProducts as $product) {
            $prices = $product->variants->pluck('price');

            $product->maxPrice = (float) $prices->max();
            $product->minPrice = (float) $prices->min();
        }

        return $next($normalisedProducts);
    }
}
