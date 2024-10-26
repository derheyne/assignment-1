<?php

declare(strict_types=1);

namespace App\Http\Integrations\SourceApi\Data\Objects;

use Spatie\LaravelData\Data;

class InventoryLogObjectData extends Data
{
    public string $id;

    public string $hash;

    public int $quantity;
}
