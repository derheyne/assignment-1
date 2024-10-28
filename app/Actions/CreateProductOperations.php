<?php
declare(strict_types=1);

namespace App\Actions;

use App\Data\InternalProductData;
use App\Data\ProductOperationData;
use App\Enums\ProductOperationEnum;
use App\Http\Integrations\TargetApi\Data\Objects\ProductObjectData;
use App\Http\Integrations\TargetApi\TargetApi;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

class CreateProductOperations
{
    public function __construct(
        protected readonly TargetApi $targetApi,
    ) {
    }

    /**
     * @param  Collection<InternalProductData>  $normalisedProducts
     * @return Collection<ProductOperationData>
     */
    public function handle(Collection $normalisedProducts): Collection
    {
        $targetProductSkus = $this->getProductsFromTargetApi(['sku'])->collect()->pluck('sku');
        $normalisedProductSkus = $normalisedProducts->pluck('sku');

        $operations = collect();

        $productSkusToDelete = $targetProductSkus->diff($normalisedProductSkus);
        /** @var Collection<InternalProductData> $normalisedProducts */
        $normalisedProducts = $normalisedProducts->reject(
            fn(InternalProductData $product) => $productSkusToDelete->contains($product->sku),
        )->collect();

        foreach ($productSkusToDelete as $productSku) {
            $operations->push(
                new ProductOperationData(
                    operation: ProductOperationEnum::DELETE,
                    product: new InternalProductData(
                        sku: $productSku,
                        title: null,
                        type: null,
                        minPrice: null,
                        maxPrice: null,
                        inventoryPolicy: null,
                        taxable: null,
                        tags: [],
                        variants: collect(),
                    ),
                ),
            );
        }

        foreach ($normalisedProducts as $product) {
            if ($targetProductSkus->contains($product->sku)) {
                $operations->push(new ProductOperationData(operation: ProductOperationEnum::UPDATE, product: $product));
            } else {
                $operations->push(new ProductOperationData(operation: ProductOperationEnum::CREATE, product: $product));
            }
        }

        return $operations;
    }

    /** @return LazyCollection<int, ProductObjectData> */
    protected function getProductsFromTargetApi(array $fields = []): LazyCollection
    {
        return $this->targetApi->products()->listProductsPaginated(fields: $fields)->collect();
    }
}
