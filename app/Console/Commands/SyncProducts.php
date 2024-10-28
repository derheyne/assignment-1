<?php

namespace App\Console\Commands;

use App\Actions\CommitProductOperations;
use App\Actions\CreateProductOperations;
use App\Http\Integrations\SourceApi\SourceApi;
use App\Hydrators\SourceVariantsToInternalProductHydrator;
use App\Pipes\ProductDataEnhancers\EnhanceProductMinMaxPrice;
use App\Pipes\ProductDataEnhancers\EnhanceProductTags;
use App\Pipes\ProductDataEnhancers\RejectDraftVariants;
use Illuminate\Console\Command;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Log;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

class SyncProducts extends Command
{
    protected $signature = 'sync:products';

    protected $description = 'Sync product data from source API';

    public function handle(
        SourceApi $sourceApi,
        Pipeline $pipeline,
        CreateProductOperations $createProductOperations,
        CommitProductOperations $commitProductOperations,
        SourceVariantsToInternalProductHydrator $sourceVariantsToLocalProductHydrator,
    ): void {
        // fetch data from flat feed from the source api
        try {
            $request = $sourceApi->getProductFeedPaginated();

            $variants = collect();
            foreach ($request->items() as $variant) {
                $variants->push($variant);
            }
        } catch (RequestException|FatalRequestException $exception) {
            Log::error(__CLASS__.': Could not fetch source list of variants', [
                'exception' => $exception,
                'responseStatus' => $exception->getStatus(),
            ]);

            report($exception);

            $this->fail('Unable to fetch list of variants from feed.');
        }

        // normalise data into a local product-variant DTO
        $normalisedProducts = $variants
            ->groupBy('parentSku')
            ->map(
                fn($product) => $sourceVariantsToLocalProductHydrator->hydrate($product),
            )
            ->collect();

        // validate data (?)

        // enhance data if necessary (?)
        // - determine tags for product and variants
        // - set min and max price for a product
        $normalisedProducts = $pipeline->send($normalisedProducts)
            ->through([
                EnhanceProductTags::class,
                EnhanceProductMinMaxPrice::class,
            ])
            ->thenReturn();

        // create a list of create-update-delete operations based on what the data needs
        // - find all products that need to be deleted and remove those products from the list for further operations
        // - determine if a product needs to be created or updated
        // and commit operations to the target api
        $commitProductOperations->handle(
            operations: $createProductOperations->handle($normalisedProducts),
        );
    }
}
