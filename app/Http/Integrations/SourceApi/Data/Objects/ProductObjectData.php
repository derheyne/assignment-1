<?php

declare(strict_types=1);

namespace App\Http\Integrations\SourceApi\Data\Objects;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
class ProductObjectData extends Data
{
    public int $id;

    public ?string $title;

    public ?string $subtitle;

    public ?string $status;

    #[MapInputName('desc')]
    #[MapOutputName('desc')]
    public ?string $description;

    public ?string $sku;

    public ?string $barcode;

    public ?string $tags;

    public ?string $price;

    public ?string $compareAtPrice;

    public ?string $inventoryPolicy;

    /** @var Collection<int, AttributeObjectData> */
    public Collection $attributes;

    public bool $taxable;

    public ?string $weight;

    public ?string $weightUnit;

    /** @var Collection<int, InventoryLogObjectData> */
    public Collection $inventoryLog;

    public ?string $parentSku;

    public ?string $parentTitle;

    public ?string $productType;

    public ?string $countryOfOrigin;

    /** @var array<int, string> */
    public array $categories;

    /** @var array<int, string> */
    public array $amazonCategories;

    /** @var array<int, string> */
    public array $tbCategories;

    public Carbon $createdAt;

    public Carbon $updatedAt;
}
