<?php
declare(strict_types=1);

namespace App\Actions;

use App\Data\ProductOperationData;
use App\Enums\ProductOperationEnum;
use App\Http\Integrations\TargetApi\Data\Objects\ProductObjectData;
use App\Http\Integrations\TargetApi\TargetApi;
use App\Hydrators\LocalProductToTargetProductHydrator;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class CommitProductOperations
{
    public function __construct(
        protected readonly TargetApi $targetApi,
        protected readonly LocalProductToTargetProductHydrator $localProductToTargetProductHydrator,
    ) {
    }

    /** @param  Collection<ProductOperationData>  $operations */
    public function handle(Collection $operations): void
    {
        foreach ($operations as $operation) {
            Log::info(__CLASS__.': Committing CUD operation for product', [
                'operationType' => $operation->operation->value,
                'sku' => $operation->product->sku,
            ]);
            $handler = $this->getOperationHandler($operation->operation);
            $handler($operation);
        }
    }

    /** @return callable(ProductOperationData): void */
    protected function getOperationHandler(ProductOperationEnum $operationType): callable
    {
        return match ($operationType) {
            ProductOperationEnum::CREATE => $this->handleCreate(...),
            ProductOperationEnum::UPDATE => $this->handleUpdate(...),
            ProductOperationEnum::DELETE => $this->handleDelete(...),
        };
    }

    protected function handleCreate(ProductOperationData $operation): void
    {
        try {
            $this->targetApi->products()->createProduct(
                product: $this->hydrateProductDataForRequest($operation),
            );
        } catch (Exception $exception) {
            Log::error(__CLASS__.': Unable to commit create operation for product', [
                'sku' => $operation->product->sku,
                'exception' => $exception,
            ]);

            report($exception);
        }
    }

    protected function handleUpdate(ProductOperationData $operation): void
    {
        try {
            $this->targetApi->products()->updateProduct(
                sku: $operation->product->sku,
                product: $this->hydrateProductDataForRequest($operation),
            );
        } catch (Exception $exception) {
            Log::error(__CLASS__.': Unable to commit update operation for product', [
                'sku' => $operation->product->sku,
                'exception' => $exception,
            ]);

            report($exception);
        }
    }

    protected function handleDelete(ProductOperationData $operation): void
    {
        try {
            $this->targetApi->products()->deleteProduct(sku: $operation->product->sku);
        } catch (Exception $exception) {
            Log::error(__CLASS__.': Unable to commit delete operation for product', [
                'sku' => $operation->product->sku,
                'exception' => $exception,
            ]);

            report($exception);
        }
    }

    protected function hydrateProductDataForRequest(ProductOperationData $operation): ProductObjectData
    {
        return $this->localProductToTargetProductHydrator->hydrate($operation->product);
    }
}
