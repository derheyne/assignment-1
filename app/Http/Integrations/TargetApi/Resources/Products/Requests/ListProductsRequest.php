<?php

declare(strict_types=1);

namespace App\Http\Integrations\TargetApi\Resources\Products\Requests;

use App\Http\Integrations\TargetApi\Data\Products\ListProductsResponseData;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\Contracts\Paginatable;

class ListProductsRequest extends Request implements Paginatable
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/products';
    }

    public function createDtoFromResponse(Response $response): ListProductsResponseData
    {
        return ListProductsResponseData::from($response->json());
    }
}
