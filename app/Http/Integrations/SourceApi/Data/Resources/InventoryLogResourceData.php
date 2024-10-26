<?php

declare(strict_types=1);

namespace App\Http\Integrations\SourceApi\Data\Resources;

use Spatie\LaravelData\Data;

class InventoryLogResourceData extends Data
{
    public string $id;

    public string $hash;

    public int $quantity;
}
