<?php

declare(strict_types=1);

namespace App\Http\Integrations\TargetApi\Resources\Products\Requests;

use App\Http\Integrations\TargetApi\Data\Objects\ProductObjectData;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class UpdateProductRequest extends Request
{
    protected Method $method = Method::PUT;

    public function __construct(
        protected readonly string $sku,
        protected readonly ProductObjectData $product,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/products/'.$this->sku;
    }

    public function defaultBody(): array
    {
        return $this->product->toArray();
    }

    public function createDtoFromResponse(Response $response): ProductObjectData
    {
        return ProductObjectData::from($response->json());
    }
}
