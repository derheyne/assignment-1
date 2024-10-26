<?php

declare(strict_types=1);

namespace App\Http\Integrations\SourceApi\Data;

use Spatie\LaravelData\Data;

class LinkData extends Data
{
    public ?string $url;

    public string $label;

    public bool $active;
}
