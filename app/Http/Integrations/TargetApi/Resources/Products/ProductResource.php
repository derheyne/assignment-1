<?php

declare(strict_types=1);

namespace App\Http\Integrations\TargetApi\Resources\Products;

use App\Http\Integrations\TargetApi\Data\Objects\ProductObjectData;
use App\Http\Integrations\TargetApi\Resources\Products\Requests\CreateProductRequest;
use App\Http\Integrations\TargetApi\Resources\Products\Requests\DeleteProductRequest;
use App\Http\Integrations\TargetApi\Resources\Products\Requests\GetProductRequest;
use App\Http\Integrations\TargetApi\Resources\Products\Requests\ListProductsRequest;
use App\Http\Integrations\TargetApi\Resources\Products\Requests\UpdateProductRequest;
use App\Http\Integrations\TargetApi\TargetApi;
use Saloon\Http\BaseResource;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\PagedPaginator;

/** @property TargetApi $connector */
class ProductResource extends BaseResource
{
    public function listProducts(
        ?int $page = null,
        string $sort = 'id',
        string $sortDir = 'asc',
        array $fields = [],
    ): Response {
        return $this->connector->send($this->createGetProductsRequest(
            page: $page,
            sort: $sort,
            sortDir: $sortDir,
            fields: $fields,
        ));
    }

    public function listProductsPaginated(
        string $sort = 'id',
        string $sortDir = 'asc',
        array $fields = [],
    ): PagedPaginator {
        return $this->connector->paginate($this->createGetProductsRequest(
            sort: $sort,
            sortDir: $sortDir,
            fields: $fields,
        ));
    }

    public function getProduct(string $sku): Response
    {
        return $this->connector->send(new GetProductRequest($sku));
    }

    public function createProduct(ProductObjectData $product): Response
    {
        return $this->connector->send(new CreateProductRequest(product: $product));
    }

    public function updateProduct(string $sku, ProductObjectData $product): Response
    {
        return $this->connector->send(new UpdateProductRequest(sku: $sku, product: $product));
    }

    public function deleteProduct(string $sku): Response
    {
        return $this->connector->send(new DeleteProductRequest($sku));
    }

    protected function createGetProductsRequest(
        ?int $page = null,
        string $sort = 'id',
        string $sortDir = 'asc',
        array $fields = [],
    ): ListProductsRequest {
        $request = new ListProductsRequest;

        $request->query()->add('sort', $this->buildSortFromFieldAndDirection($sort, $sortDir));

        if (! is_null($page)) {
            $request->query()->add('page', $page);
        }

        if ($fields) {
            $request->query()->add('fields[products]', implode(',', $fields));
        }

        return $request;
    }

    protected function buildSortFromFieldAndDirection(string $field, string $direction): string
    {
        $directionModifier = $direction === 'desc' ? '-' : '';

        return $directionModifier.$field;
    }
}
