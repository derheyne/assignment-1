<?php

declare(strict_types=1);

namespace App\Http\Integrations\SourceApi\Data\Resources;

use Spatie\LaravelData\Data;

class AttributeResourceData extends Data
{
    public string $id;

    public string $type;

    public string|array $value;
}
