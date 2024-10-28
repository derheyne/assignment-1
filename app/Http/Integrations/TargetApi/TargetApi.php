<?php

declare(strict_types=1);

namespace App\Http\Integrations\TargetApi;

use App\Http\Integrations\TargetApi\Resources\Products\ProductResource;
use Illuminate\Support\Facades\Log;
use Saloon\Contracts\Authenticator;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\Connector;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\PagedPaginator;
use Saloon\Traits\Plugins\AcceptsJson;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;

class TargetApi extends Connector
{
    use AcceptsJson;
    use AlwaysThrowOnErrors;

    public ?int $tries = 3;

    public function resolveBaseUrl(): string
    {
        return config('services.target-api.base_url');
    }

    protected function defaultAuth(): Authenticator
    {
        return new TokenAuthenticator(config('services.target-api.key'));
    }

    public function products(): ProductResource
    {
        return new ProductResource($this);
    }

    public function paginate(Request $request): PagedPaginator
    {
        return new class(connector: $this, request: $request) extends PagedPaginator
        {
            protected function isLastPage(Response $response): bool
            {
                return is_null($response->json('next_page_url'));
            }

            protected function getPageItems(Response $response, Request $request): array
            {
                return $response->dtoOrFail()->data->all();
            }

            protected function applyPagination(Request $request): Request
            {
                $request->query()->add('page', $this->currentPage);

                return $request;
            }

            protected function getTotalPages(Response $response): int
            {
                return $response->json('last_page');
            }
        };
    }

    public function handleRetry(FatalRequestException|RequestException $exception, Request $request): bool
    {
        $response = $exception->getResponse();

        Log::warning(__CLASS__.': Request resulted in an error. Retrying ...', [
            'requestEndpoint' => $response->getPsrRequest()->getUri(),
            'requestMethod' => $request->getMethod()->value,
            'responseBody' => $response->body(),
            'responseStatus' => $response->status(),
        ]);

        return true;
    }
}
