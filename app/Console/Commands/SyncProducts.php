<?php

namespace App\Console\Commands;

use App\Actions\CommitProductOperations;
use App\Actions\CreateProductOperations;
use App\Http\Integrations\SourceApi\Data\ProductFeed\ProductFeedResponseData;
use App\Http\Integrations\SourceApi\SourceApi;
use App\Hydrators\SourceVariantsToInternalProductHydrator;
use App\Pipes\ProductDataEnhancers\EnhanceProductMinMaxPrice;
use App\Pipes\ProductDataEnhancers\EnhanceProductTags;
use App\Pipes\ProductDataEnhancers\EnhanceProductType;
use App\Pipes\ProductDataEnhancers\EnhanceVariantSku;
use App\Pipes\ProductDataEnhancers\EnhanceVariantTitle;
use App\Pipes\ProductDataEnhancers\RejectVariantsWithDuplicateSku;
use App\Pipes\ProductDataEnhancers\RejectVariantsWithoutPrice;
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
            // this is a workaround, because using the paginator appears to create duplicate variants.
            $currentPage = 0;
            $variants = collect();
            while (true) {
                $response = $sourceApi->getProductFeed(++$currentPage);

                /** @var ProductFeedResponseData $data */
                $responseData = $response->dtoOrFail();
                $variants = $variants->merge($responseData->data);

                if ($responseData->lastPage === $currentPage) {
                    break;
                }
            }
        } catch (RequestException|FatalRequestException $exception) {
            Log::error(__CLASS__.': Could not fetch source list of variants', [
                'exception' => $exception,
            ]);

            report($exception);

            $this->fail('Unable to fetch list of variants from feed.');
        }

        // normalise data into a local product-variant DTO
        $this->comment('Normalising data ...');

        $normalisedProducts = $variants
            ->groupBy('parentSku')
            ->map(
                fn($product) => $sourceVariantsToLocalProductHydrator->hydrate($product),
            );

        // validate data (?)
        $this->comment('Enhancing data ...');

        // enhance data if necessary (?)
        // - determine tags for product and variants
        // - set min and max price for a product
        $normalisedProducts = $pipeline->send($normalisedProducts)
            ->through([
                RejectVariantsWithDuplicateSku::class,
                RejectVariantsWithoutPrice::class,
                EnhanceVariantSku::class,
                EnhanceProductTags::class,
                EnhanceProductMinMaxPrice::class,
                EnhanceVariantTitle::class,
                EnhanceProductType::class,
            ])
            ->thenReturn();

        // create a list of create-update-delete operations based on what the data needs
        // - find all products that need to be deleted and remove those products from the list for further operations
        // - determine if a product needs to be created or updated
        // and commit operations to the target api
        $this->comment('Committing operations ...');

        $commitProductOperations->handle(
            operations: $createProductOperations->handle($normalisedProducts),
        );

        $this->info('Success!');
    }
}
