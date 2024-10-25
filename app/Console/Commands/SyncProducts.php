<?php

namespace App\Console\Commands;

use http\Exception\InvalidArgumentException;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncProducts extends Command
{
    protected const REQUEST_ERROR_THRESHOLD = 3;

    protected const REQUEST_ERROR_TIMEOUT_SECONDS = 1;

    protected $signature = 'sync:products';

    protected $description = 'Sync product data from source API';

    public function handle(): void
    {
        // fetch variants available in source system
        $sourceVariants = collect();
        $page = 1;
        $requestErrorCount = 0;
        while (true) {
            Log::debug('Fetching page of flat feed from source API', [
                'page' => $page,
                'totalPages' => isset($response) ? $response->json()['last_page'] ?? 0 : 0,
            ]);

            $response = $this->prepareHttpClient('source-api')
                ->get('flat-feed?page='.$page.'&sort=sku');

            if ($response->failed()) {
                if (++$requestErrorCount > self::REQUEST_ERROR_THRESHOLD) {
                    Log::error('Error fetching products from source API. Aborting.', [
                        'responseBody' => $response->body(),
                        'responseStatus' => $response->status(),
                        'page' => $page,
                        'errorCount' => $requestErrorCount,
                    ]);

                    break;
                }

                Log::warning('Problem fetching products from source API. Retrying ...', [
                    'responseBody' => $response->body(),
                    'responseStatus' => $response->status(),
                    'page' => $page,
                    'errorCount' => $requestErrorCount,
                ]);

                // Circumvent API timeout.
                // There are no headers indicating how many calls we can do per second
                sleep(self::REQUEST_ERROR_TIMEOUT_SECONDS);

                continue;
            }

            $responseBody = $response->json();
            $lastPage = $responseBody['last_page'];

            $sourceVariants = $sourceVariants->merge($responseBody['data']);

            if ($page === $lastPage) {
                break;
            }

            $page++;
            $requestErrorCount = 0;
        }

        $variantsGrouped = $sourceVariants->groupBy('parent_sku');

        // fetch products available in target system
        $page = 1;
        $targetProducts = collect();
        while (true) {
            Log::debug('Fetching page from product endpoint of target API', [
                'page' => $page,
                'totalPages' => isset($response) ? $response->json()['last_page'] ?? '?' : '?',
            ]);

            $this->prepareHttpClient('target-api')
                ->get(config('services.target-api.base_url').'products?page='.$page.'&sort=sku');

            if ($response->failed()) {
                if (++$requestErrorCount > self::REQUEST_ERROR_THRESHOLD) {
                    Log::error('Error fetching products from source API. Aborting.', [
                        'responseBody' => $response->body(),
                        'responseStatus' => $response->status(),
                        'page' => $page,
                        'errorCount' => $requestErrorCount,
                    ]);

                    break;
                }

                Log::warning('Error fetching products from target API. Retrying ...', [
                    'responseBody' => $response->body(),
                    'responseStatus' => $response->status(),
                    'page' => $page,
                    'errorCount' => $requestErrorCount,
                ]);

                // Circumvent API timeout.
                // There are no headers indicating how many calls we can do per second
                sleep(self::REQUEST_ERROR_TIMEOUT_SECONDS);

                continue;
            }

            $responseBody = $response->json();
            $lastPage = $responseBody['last_page'];

            $targetProducts = $targetProducts->merge($responseBody['data']);

            if ($page === $lastPage) {
                break;
            }

            $page++;
            $requestErrorCount = 0;
        }

        $targetProducts = $targetProducts->keyBy('sku');

        // delete products and remove them from the local list
        foreach ($targetProducts as $sku => $product) {
            // skip skus that exist in both the source and target systems
            if ($variantsGrouped->has($sku)) {
                continue;
            }

            Log::info('Deleting product from target API.', [
                'sku' => $sku,
            ]);

            // delete product from target system
            $this->prepareHttpClient('target-api')
                ->delete(config('services.target-api.base_url').'products/'.$sku);

            if ($response->failed()) {
                Log::error('Unable to delete product from target API.', [
                    'responseBody' => $response->body(),
                    'responseStatus' => $response->status(),
                    'sku' => $sku,
                ]);
            }

            $variantsGrouped->forget($sku);
        }

        foreach ($variantsGrouped as $parentSku => $sourceVariants) {
            try {
                $transformedProductData = $this->transformVariantsToProduct($sourceVariants);
            } catch (Throwable $exception) {
                Log::error('Unable to transform product data', [
                    'sku' => $parentSku,
                    'exception' => $exception,
                ]);

                continue;
            }

            if (! $targetProduct = $targetProducts->get($parentSku)) {
                Log::info('Creating new product on target API.', [
                    'sku' => $parentSku,
                ]);

                $this->prepareHttpClient('target-api')
                    ->post(config('services.target-api.base_url').'products', $transformedProductData);

                if ($response->failed()) {
                    Log::error('Unable to create product on target API.', [
                        'responseBody' => $response->body(),
                        'responseStatus' => $response->status(),
                        'sku' => $parentSku,
                        'data' => $transformedProductData,
                    ]);
                }

                continue;
            }

            Log::info('Updating product on target API.', [
                'sku' => $parentSku,
            ]);

            $response = $this->prepareHttpClient('target-api')
                ->put('products/'.$parentSku, $transformedProductData);

            if ($response->failed()) {
                Log::error('Unable to create product on target API.', [
                    'responseBody' => $response->body(),
                    'responseStatus' => $response->status(),
                    'sku' => $parentSku,
                    'data' => $transformedProductData,
                ]);
            }

            // Couldn't figure out why this wasn't working. Need to investiage. For now, we're just updating the product
            // regardless if something has changed or not.
            /*try {
                $latestUpdateAt = $sourceVariants->sortByDesc('updated_at')->first()['updated_at'];

                if ($latestUpdateAt > $targetProduct['updated_at']) {
                    Log::info('Updating product on target API.', [
                        'sku' => $parentSku,
                        'latestUpdateAtTarget' => $targetProduct['updated_at'],
                        'latestUpdateAtSource' => $latestUpdateAt,
                    ]);

                    // update product
                }
            } catch (Throwable $exception) {
                dd($parentSku, $sourceVariants->sortByDesc('updated_at'),
                    $sourceVariants->sortByDesc('updated_at')->first());
            }*/
        }
    }

    protected function transformVariantsToProduct(Collection $variants): array
    {
        $productData = $variants->first();

        $prices = $variants->pluck('price');

        $productTags = $variants
            ->pluck('tags')
            ->map(fn($tagString) => explode(',', $tagString))
            ->flatten()
            ->unique()
            ->map('trim');

        $transformedVariants = [];

        foreach ($variants as $variant) {
            $transformedVariants[] = [
                'sku' => $variant['sku'],
                'desc' => $variant['desc'],
                'tags' => array_unique(explode(',', $variant['tags'])),
                'price' => $variant['price'],
                'title' => $variant['title'],
                'subtitle' => $variant['subtitle'],
                'compare_at_price' => $variant['compare_at_price'],
            ];
        }

        return [
            'title' => $productData['parent_title'],
            'sku' => $productData['parent_sku'],
            'type' => $productData['product_type'],
            'inventory_policy' => $productData['inventory_policy'],
            'taxable' => $productData['taxable'] ? 1 : 0,
            'min_price' => $prices->min(),
            'max_price' => $prices->max(),
            'tags' => $productTags,
            'variants' => $transformedVariants,
        ];
    }

    protected function prepareHttpClient(string $service)
    {
        $baseUrl = config('services.'.$service.'.base_url');

        if (! $baseUrl) {
            throw new InvalidArgumentException('Base URL for service ['.$service.'] could not be found.');
        }

        return Http::withHeaders([
            'Authorization' => 'Bearer '.env('SOURCE_API_KEY'),
        ])
            ->baseUrl($baseUrl);
    }
}
