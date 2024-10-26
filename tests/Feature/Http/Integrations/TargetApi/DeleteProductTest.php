<?php

declare(strict_types=1);

use App\Http\Integrations\TargetApi\Data\Objects\ProductObjectData;
use Saloon\Http\Faking\MockResponse;
use Tests\Feature\Http\Integrations\TargetApi\Concerns\TargetApiHelpers;

uses(TargetApiHelpers::class);

it('can delete a product by SKU', function () {
    [, $connector] = $this->createTargetApiConnector([
        MockResponse::make(body: loadJsonMockFile(__DIR__.'/fixtures/GetProductRequest_200_5335DUXE2.json')),
    ]);

    $response = $connector->products()->updateProduct(
        sku: '5335DUXE2',
        product: ProductObjectData::from(loadJsonMockFile(__DIR__.'/fixtures/GetProductRequest_200_5335DUXE2.json')),
    );

    expect($response->status())->toBe(200)
        ->and($response->dto())->toBeInstanceOf(ProductObjectData::class);
});
