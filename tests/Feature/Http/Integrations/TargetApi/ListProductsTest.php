<?php

declare(strict_types=1);

use App\Http\Integrations\TargetApi\Data\Objects\ProductObjectData;
use App\Http\Integrations\TargetApi\Data\Products\ListProductsResponseData;
use Illuminate\Support\Collection;
use Saloon\Exceptions\Request\ClientException;
use Saloon\Exceptions\Request\Statuses\InternalServerErrorException;
use Saloon\Http\Faking\MockResponse;
use Tests\Feature\Http\Integrations\TargetApi\Concerns\TargetApiHelpers;

uses(TargetApiHelpers::class);

it('can request and transform a response', function () {
    [, $connector] = $this->createTargetApiConnector([
        MockResponse::make(body: loadJsonMockFile(__DIR__.'/fixtures/ListProductsRequest_200_page1.json')),
    ]);

    $response = $connector->products()->listProducts();
    /** @var ListProductsResponseData $dto */
    $dto = $response->dtoOrFail();

    expect($dto)->toBeInstanceOf(ListProductsResponseData::class)
        ->and($dto->data)->toBeInstanceOf(Collection::class)->toHaveCount(15)
        ->and($dto->data->first()->sku)->toEqual('1231DUFL');
});

it('can paginate and transform a paginated response', function () {
    [, $connector] = $this->createTargetApiConnector([
        MockResponse::make(body: loadJsonMockFile(__DIR__.'/fixtures/ListProductsRequest_200_page1.json')),
        MockResponse::make(body: loadJsonMockFile(__DIR__.'/fixtures/ListProductsRequest_200_page2.json')),
    ]);

    $response = $connector->products()->listProductsPaginated();

    // Workaround. By using $response->collect(), we'd run into the following error:
    // > Saloon was unable to guess a mock response for your request
    // > [https://target-api.local/api/flat-feed?sort=id&page=0], consider using a wildcard url mock or a connector mock.
    $items = collect();
    foreach ($response->items() as $item) {
        $items->push($item);
    }

    expect($items)
        ->toHaveCount(25)
        ->toContainOnlyInstancesOf(ProductObjectData::class);
});

it('can correctly assign parameters', function () {
    // page
    [, $connector] = $this->createTargetApiConnector([MockResponse::make()]);
    $response = $connector->products()->listProducts(page: 5);
    expect($response->getRequest()->query()->get('page'))->toBe(5);

    // sortAsc
    [, $connector] = $this->createTargetApiConnector([MockResponse::make()]);
    $response = $connector->products()->listProducts(sort: 'sku', sortDir: 'asc');
    expect($response->getRequest()->query()->get('sort'))->toBe('sku');

    // sortDesc
    [, $connector] = $this->createTargetApiConnector([MockResponse::make()]);
    $response = $connector->products()->listProducts(sort: 'sku', sortDir: 'desc');
    expect($response->getRequest()->query()->get('sort'))->toBe('-sku');

    // sortDefault
    [, $connector] = $this->createTargetApiConnector([MockResponse::make()]);
    $response = $connector->products()->listProducts(sort: 'sku');
    expect($response->getRequest()->query()->get('sort'))->toBe('sku');

    // sortDefault
    [, $connector] = $this->createTargetApiConnector([MockResponse::make()]);
    $response = $connector->products()->listProducts(fields: ['id', 'sku']);
    expect($response->getRequest()->query()->get('fields[products]'))->toBe('id,sku');
});

it('can retry request requests resulting in a server error', function () {
    [$mockClient, $connector] = $this->createTargetApiConnector([
        MockResponse::make(
            body: loadJsonMockFile(__DIR__.'/fixtures/ListProductsRequest_500.json'),
            status: 500,
        ),
        MockResponse::make(
            body: loadJsonMockFile(__DIR__.'/fixtures/ListProductsRequest_200_page1.json'),
        ),
    ]);

    $response = $connector->products()->listProducts();

    $mockClient->assertSentCount(2);
    expect($response->status())->toBe(200);
});

it('fails after 3 retries', function () {
    [$mockClient, $connector] = $this->createTargetApiConnector([
        MockResponse::make(
            body: loadJsonMockFile(__DIR__.'/fixtures/ListProductsRequest_500.json'),
            status: 500,
        ),
        MockResponse::make(
            body: loadJsonMockFile(__DIR__.'/fixtures/ListProductsRequest_500.json'),
            status: 500,
        ),
        MockResponse::make(
            body: loadJsonMockFile(__DIR__.'/fixtures/ListProductsRequest_500.json'),
            status: 500,
        ),
    ]);

    $connector->products()->listProducts();

    $mockClient->assertSentCount(3);
})->throws(InternalServerErrorException::class);

it('can not retry requests resulting in a client error', function () {
    [, $connector] = $this->createTargetApiConnector([MockResponse::make(status: 400)]);

    $connector->products()->listProducts();
})->throws(ClientException::class);
