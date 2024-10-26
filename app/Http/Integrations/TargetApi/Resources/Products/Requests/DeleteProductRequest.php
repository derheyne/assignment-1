<?php

declare(strict_types=1);

namespace App\Http\Integrations\TargetApi\Resources\Products\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class DeleteProductRequest extends Request
{
    protected Method $method = Method::DELETE;

    public function __construct(protected readonly string $sku) {}

    public function resolveEndpoint(): string
    {
        return '/products/'.$this->sku;
    }
}
