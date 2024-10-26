<?php

declare(strict_types=1);

use App\Http\Integrations\SourceApi\Data\ProductFeed\ProductFeedResponseData;
use App\Http\Integrations\SourceApi\SourceApi;
use Illuminate\Support\Collection;
use Saloon\Exceptions\Request\Statuses\InternalServerErrorException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

/** @return array{0: MockClient, 1: SourceApi} */
function createSourceApiConnector(array $mocks = []): array
{
    $mockClient = new MockClient($mocks);

    $connector = new SourceApi;
    $connector->withMockClient($mockClient);

    return [$mockClient, $connector];
}

it('can request and transform a response', function () {
    [, $connector] = createSourceApiConnector([
        MockResponse::make(body: loadJsonMockFile(__DIR__.'/fixtures/ProductFeedRequest_200_page1.json')),
    ]);

    $response = $connector->getProductFeed();
    /** @var ProductFeedResponseData $dto */
    $dto = $response->dtoOrFail();

    expect($dto)->toBeInstanceOf(ProductFeedResponseData::class)
        ->and($dto->data)->toBeInstanceOf(Collection::class)->toHaveCount(15)
        ->and($dto->data->first()->id)->toEqual(579)
        ->and($dto->data->first()->sku)->toEqual('VNQC7424-XL');
});

it('can paginate and transform a paginated response', function () {
    [, $connector] = createSourceApiConnector([
        MockResponse::make(body: loadJsonMockFile(__DIR__.'/fixtures/ProductFeedRequest_200_page1.json')),
        MockResponse::make(body: loadJsonMockFile(__DIR__.'/fixtures/ProductFeedRequest_200_page2.json')),
    ]);

    $response = $connector->getProductFeedPaginated();

    $items = collect();
    foreach ($response->items() as $item) {
        $items->push($item);
    }

    expect($items)->toHaveCount(29);
});

it('can correctly assign parameters', function () {
    // page
    [, $connector] = createSourceApiConnector([MockResponse::make()]);
    $response = $connector->getProductFeed(page: 5);
    expect($response->getRequest()->query()->get('page'))->toBe(5);

    // sortAsc
    [, $connector] = createSourceApiConnector([MockResponse::make()]);
    $response = $connector->getProductFeed(sort: 'sku', sortDir: 'asc');
    expect($response->getRequest()->query()->get('sort'))->toBe('sku');

    // sortDesc
    [, $connector] = createSourceApiConnector([MockResponse::make()]);
    $response = $connector->getProductFeed(sort: 'sku', sortDir: 'desc');
    expect($response->getRequest()->query()->get('sort'))->toBe('-sku');

    // sortDefault
    [, $connector] = createSourceApiConnector([MockResponse::make()]);
    $response = $connector->getProductFeed(sort: 'sku');
    expect($response->getRequest()->query()->get('sort'))->toBe('sku');

    // sortDefault
    [, $connector] = createSourceApiConnector([MockResponse::make()]);
    $response = $connector->getProductFeed(fields: ['id', 'sku']);
    expect($response->getRequest()->query()->get('fields[catalog_entries]'))->toBe('id,sku');
});

it('can retry a request', function () {
    [$mockClient, $connector] = createSourceApiConnector([
        MockResponse::make(
            body: loadJsonMockFile(__DIR__.'/fixtures/ProductFeedRequest_500.json'),
            status: 500,
        ),
        MockResponse::make(
            body: loadJsonMockFile(__DIR__.'/fixtures/ProductFeedRequest_200_page1.json'),
        ),
    ]);

    $response = $connector->getProductFeed();

    $mockClient->assertSentCount(2);
    expect($response->status())->toBe(200);
});

it('fails after 3 retries', function () {
    [$mockClient, $connector] = createSourceApiConnector([
        MockResponse::make(
            body: loadJsonMockFile(__DIR__.'/fixtures/ProductFeedRequest_500.json'),
            status: 500,
        ),
        MockResponse::make(
            body: loadJsonMockFile(__DIR__.'/fixtures/ProductFeedRequest_500.json'),
            status: 500,
        ),
        MockResponse::make(
            body: loadJsonMockFile(__DIR__.'/fixtures/ProductFeedRequest_500.json'),
            status: 500,
        ),
    ]);

    $connector->getProductFeed();

    $mockClient->assertSentCount(3);
})->throws(InternalServerErrorException::class);