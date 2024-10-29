<?php
declare(strict_types=1);

namespace App\Hydrators;

use App\Data\InternalProductData;
use App\Data\InternalVariantData;
use App\Http\Integrations\TargetApi\Data\Objects\ProductObjectData;
use App\Http\Integrations\TargetApi\Data\Objects\VariantObjectData;
use Spatie\LaravelData\Optional;

class LocalProductToTargetProductHydrator
{
    public function hydrate(InternalProductData $data): ProductObjectData
    {
        $variants = $data->variants->map(fn(InternalVariantData $variant) => new VariantObjectData(
            sku: $variant->sku,
            description: $variant->description,
            tags: $variant->tags,
            price: $variant->price,
            title: $variant->title,
            subtitle: $variant->subtitle,
            compareAtPrice: $variant->compareAtPrice,
        ));

        return new ProductObjectData(
            sku: $data->sku,
            title: $data->title,
            type: $data->type,
            inventoryPolicy: $data->inventoryPolicy,
            taxable: $data->taxable ? '1' : '0',
            minPrice: (string) $data->minPrice,
            maxPrice: (string) $data->maxPrice,
            tags: $data->tags ?: [],
            variants: $variants,
            createdAt: Optional::create(),
            updatedAt: Optional::create(),
        );
    }
}
