<?php
declare(strict_types=1);

use App\Http\Integrations\SourceApi\Requests\ProductFeedRequest;
use App\Http\Integrations\SourceApi\SourceApi;
use App\Http\Integrations\TargetApi\Resources\Products\Requests\CreateProductRequest;
use App\Http\Integrations\TargetApi\Resources\Products\Requests\DeleteProductRequest;
use App\Http\Integrations\TargetApi\Resources\Products\Requests\ListProductsRequest;
use App\Http\Integrations\TargetApi\Resources\Products\Requests\UpdateProductRequest;
use App\Http\Integrations\TargetApi\TargetApi;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Tests\Feature\Http\Integrations\SourceApi\Concerns\SourceApiHelpers;
use Tests\Feature\Http\Integrations\TargetApi\Concerns\TargetApiHelpers;

uses(SourceApiHelpers::class);
uses(TargetApiHelpers::class);

it('can create, update, delete products on target based on data from source', function () {
    [$sourceApiMockClient, $sourceApiConnector] = $this->createSourceApiConnector([
        ProductFeedRequest::class => MockResponse::make(
            body: loadJsonMockFile(__DIR__.'/fixtures/ProductFeedRequest_200_page1.json'),
        ),
    ]);
    $this->app->bind(SourceApi::class, fn($app) => $sourceApiConnector);

    [$targetApiMockClient, $targetApiConnector] = $this->createTargetApiConnector([
        ListProductsRequest::class => MockResponse::make(
            body: loadJsonMockFile(__DIR__.'/fixtures/ListProductsRequest_200_page1.json'),
        ),
        DeleteProductRequest::class => MockResponse::make(),
        CreateProductRequest::class => MockResponse::make(),
        UpdateProductRequest::class => MockResponse::make(),
    ]);
    $this->app->bind(TargetApi::class, fn($app) => $targetApiConnector);

    $this->artisan('sync:products')->assertOk();

    $sourceApiMockClient->assertSentCount(1);
    $sourceApiMockClient->assertSent(ProductFeedRequest::class);

    $targetApiMockClient->assertSentCount(4);
    $targetApiMockClient->assertSent(ListProductsRequest::class);
    /*$targetApiMockClient->assertSent(
        fn(Request $request, Response $response) => dump($request->getMethod()->value.' '.$request->resolveEndpoint(),
            $request instanceof HasBody ? $request->body()->all() : null),
    );*/
    // Delete operation
    $targetApiMockClient->assertSent(
        fn(Request $request, Response $response) => $request instanceof DeleteProductRequest
            && $request->getMethod() === Method::DELETE
            && $request->resolveEndpoint() === '/products/DELETE',
    );
    // Create operation
    $targetApiMockClient->assertSent(
        fn(Request $request, Response $response) => $request instanceof CreateProductRequest
            && $request->getMethod() === Method::POST
            && $request->resolveEndpoint() === '/products'
            && $request->body()->get('sku') === 'CREATE'
            && $request->body()->get('title') === 'Create product'
            && $request->body()->get('minPrice') === '13.81'
            && $request->body()->get('maxPrice') === '82.58'
            && $request->body()->get('tags') === [
                'a',
                'b',
                'eol',
                'focus',
                'front-page',
                'new',
                'operation:create',
                'pre-order',
                'sale',
                'summer',
                'wip',
            ],
    );
    // Update operation
    $targetApiMockClient->assertSent(
        fn(Request $request, Response $response) => $request instanceof UpdateProductRequest
            && $request->getMethod() === Method::PUT
            && $request->resolveEndpoint() === '/products/UPDATE'
            && $request->body()->get('sku') === 'UPDATE'
            && $request->body()->get('title') === 'Update product'
            && $request->body()->get('minPrice') === '11.42'
            && $request->body()->get('maxPrice') === '95.79'
            && $request->body()->get('tags') === [
                'a',
                'b',
                'eol',
                'focus',
                'front-page',
                'new',
                'operation:update',
                'pre-order',
                'sale',
                'summer',
                'wip',
            ],
    );
})->only();
