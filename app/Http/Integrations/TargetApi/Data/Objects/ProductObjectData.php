<?php

declare(strict_types=1);

namespace App\Http\Integrations\TargetApi\Data\Objects;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class ProductObjectData extends Data
{
    public ?string $title;

    public string $sku;

    public ?string $type;

    public ?string $inventoryPolicy;

    public ?string $taxable;

    public ?string $minPrice;

    public ?string $maxPrice;

    /** @var array<int, string> */
    public array $tags;

    /** @var Collection<int, VariantObjectData> */
    public Collection $variants;

    public Carbon $createdAt;

    public Carbon $updatedAt;
}
