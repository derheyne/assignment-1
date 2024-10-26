<?php

declare(strict_types=1);

namespace App\Http\Integrations\SourceApi\Data\Objects;

use Spatie\LaravelData\Data;

class AttributeObjectData extends Data
{
    public string $id;

    public string $type;

    public string|array $value;
}
