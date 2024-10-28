<?php
declare(strict_types=1);

namespace App\Pipes\ProductDataEnhancers;

use App\Data\InternalProductData;
use Closure;
use Illuminate\Support\Collection;

class EnhanceProductTags
{
    /** @param  Collection<InternalProductData>  $normalisedProducts */
    public function handle(Collection $normalisedProducts, Closure $next)
    {
        foreach ($normalisedProducts as $product) {
            $product->tags = $product->variants
                ->pluck('tags')
                ->flatten()
                ->unique()
                ->sort()
                ->values()
                ->toArray();
        }

        return $next($normalisedProducts);
    }
}
