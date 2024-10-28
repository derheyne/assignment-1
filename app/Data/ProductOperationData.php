<?php
declare(strict_types=1);

namespace App\Data;

use App\Enums\ProductOperationEnum;
use Spatie\LaravelData\Data;

class ProductOperationData extends Data
{
    public function __construct(
        public ProductOperationEnum $operation,
        public InternalProductData $product,
    ) {
    }
}
