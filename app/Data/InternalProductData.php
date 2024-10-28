<?php
declare(strict_types=1);

namespace App\Data;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

class InternalProductData extends Data
{
    public function __construct(
        public string $sku,
        public ?string $title = null,
        public ?string $type = null,
        public ?float $minPrice = null,
        public ?float $maxPrice = null,
        public ?string $inventoryPolicy = null,
        public ?bool $taxable = null,
        public array $tags = [],
        /** @var Collection<int, InternalVariantData> */
        public Collection $variants,
    ) {
    }
}
