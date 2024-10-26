<?php

declare(strict_types=1);

namespace App\Http\Integrations\TargetApi\Data\Objects;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class VariantObjectData extends Data
{
    public string $sku;

    #[MapInputName('desc')]
    public ?string $description;

    /** @var array<int, string> */
    public array $tags;

    public ?string $price;

    public ?string $title;

    public ?string $subtitle;

    public ?string $compareAtPrice;
}
