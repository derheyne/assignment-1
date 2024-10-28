<?php
declare(strict_types=1);

namespace App\Data;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

class InternalVariantData extends Data
{
    public function __construct(
        public ?string $sku,
        public ?string $title = null,
        public ?string $subtitle = null,
        public ?string $description = null,
        public ?string $compareAtPrice = null,
        public ?string $status = null,
        public ?string $price = null,
        /** @var string[] */
        public array $tags = [],
        /** @var Collection<int, InternalVariantAttributeData> */
        public Collection $attributes,
    ) {
    }
}
