<?php
declare(strict_types=1);

namespace App\Hydrators;

use App\Data\InternalProductData;
use App\Data\InternalVariantAttributeData;
use App\Data\InternalVariantData;
use App\Http\Integrations\SourceApi\Data\Objects\AttributeObjectData;
use App\Http\Integrations\SourceApi\Data\Objects\ProductObjectData;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SourceVariantsToInternalProductHydrator
{
    /** @param  Collection<int, ProductObjectData>  $variants */
    public function hydrate(Collection $variants): InternalProductData
    {
        $firstVariantForProductData = $variants->first();

        $hydratedVariants = collect();
        foreach ($variants as $variant) {
            $hydratedVariants->push(new InternalVariantData(
                sku: $variant->sku,
                title: $variant->title,
                subtitle: $variant->subtitle,
                description: $variant->description,
                compareAtPrice: $variant->compareAtPrice,
                status: $variant->status,
                price: $variant->price,
                tags: Str::of($variant->tags)->explode(',')->map('trim')->toArray(),
                attributes: $variant->attributes->map(
                    fn(AttributeObjectData $attribute) => new InternalVariantAttributeData(
                        id: $attribute->id,
                        type: $attribute->type,
                        value: $attribute->value,
                    ),
                ),
            ));
        }

        return new InternalProductData(
            sku: $firstVariantForProductData->parentSku,
            title: $firstVariantForProductData->parentTitle,
            type: $firstVariantForProductData->productType,
            minPrice: null,
            maxPrice: null,
            inventoryPolicy: $firstVariantForProductData->inventoryPolicy,
            taxable: (bool) $firstVariantForProductData->taxable,
            tags: [],
            variants: $hydratedVariants,
        );
    }
}
