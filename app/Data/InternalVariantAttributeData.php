<?php
declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

class InternalVariantAttributeData extends Data
{
    public function __construct(
        public string $id,
        public string $type,
        public string|array $value,
    ) {
    }
}