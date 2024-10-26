<?php

declare(strict_types=1);

namespace App\Http\Integrations\TargetApi\Resources\Products\Requests;

use App\Http\Integrations\TargetApi\Data\Objects\ProductObjectData;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class GetProductRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(protected readonly string $sku) {}

    public function resolveEndpoint(): string
    {
        return '/products/'.$this->sku;
    }

    public function createDtoFromResponse(Response $response): ProductObjectData
    {
        return ProductObjectData::from($response->json());
    }
}
