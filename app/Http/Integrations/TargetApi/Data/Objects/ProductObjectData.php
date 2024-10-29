<?php

declare(strict_types=1);

namespace App\Http\Integrations\TargetApi\Data\Objects;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Optional;

#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
class ProductObjectData extends Data
{
    public function __construct(
        public string $sku,
        public ?string $title = null,
        public ?string $type = null,
        public ?string $inventoryPolicy = null,
        public ?string $taxable = null,
        public ?string $minPrice = null,
        public ?string $maxPrice = null,
        /** @var array<int, string> */
        public array $tags = [],
        /** @var Collection<int, VariantObjectData> */
        public Collection $variants,
        public Carbon|Optional $createdAt,
        public Carbon|Optional $updatedAt,
    ) {
    }
}
