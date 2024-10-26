<?php

declare(strict_types=1);

namespace App\Http\Integrations\SourceApi\Requests;

use App\Http\Integrations\SourceApi\Data\ProductFeed\ProductFeedResponseData;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\Contracts\Paginatable;

class ProductFeedRequest extends Request implements Paginatable
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/flat-feed';
    }

    public function createDtoFromResponse(Response $response): ProductFeedResponseData
    {
        $data = $response->json();

        return ProductFeedResponseData::from($data);
    }
}
