<?php

declare(strict_types=1);

namespace App\Http\Integrations\SourceApi;

use App\Http\Integrations\SourceApi\Requests\ProductFeedRequest;
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

class SourceApi extends Connector
{
    use AcceptsJson;
    use AlwaysThrowOnErrors;

    public ?int $tries = 3;

    public function resolveBaseUrl(): string
    {
        return config('services.source-api.base_url');
    }

    protected function defaultAuth(): Authenticator
    {
        return new TokenAuthenticator(config('services.source-api.key'));
    }

    public function getProductFeed(
        int $page = 1,
        string $sort = 'id',
        string $sortDir = 'asc',
        array $fields = [],
    ): Response {
        return $this->send(
            $this->createProductFeedRequest(
                page: $page,
                sort: $sort,
                sortDir: $sortDir,
                fields: $fields,
            ),
        );
    }

    public function getProductFeedPaginated(
        string $sort = 'id',
        string $sortDir = 'asc',
        array $fields = [],
    ): PagedPaginator {
        return $this->paginate(
            $this->createProductFeedRequest(
                sort: $sort,
                sortDir: $sortDir,
                fields: $fields,
            ),
        );
    }

    protected function createProductFeedRequest(
        ?int $page = null,
        string $sort = 'id',
        string $sortDir = 'asc',
        array $fields = [],
    ): ProductFeedRequest {
        $request = new ProductFeedRequest;

        $request->query()->add('sort', $this->buildSortFromFieldAndDirection($sort, $sortDir));

        if (! is_null($page)) {
            $request->query()->add('page', $page);
        }

        if ($fields) {
            $request->query()->add('fields[catalog_entries]', implode(',', $fields));
        }

        return $request;
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
        $context = [
            'exceptionClass' => $exception::class,
        ];

        if ($exception instanceof RequestException) {
            if ($exception->getResponse()->clientError()) {
                return false;
            }

            $response = $exception->getResponse();

            $context += [
                'requestEndpoint' => $response->getPsrRequest()->getUri(),
                'requestMethod' => $request->getMethod()->value,
                'responseBody' => $response->body(),
                'responseStatus' => $response->status(),
            ];
        } else {
            $context += [
                'exception' => $exception,
                'requestEndpoint' => $request->resolveEndpoint(),
                'requestMethod' => $request->getMethod()->value,
            ];

            report($exception);
        }

        Log::warning(__CLASS__.': Request resulted in an error. Retrying ...', $context);

        return true;
    }

    protected function buildSortFromFieldAndDirection(string $field, string $direction): string
    {
        $directionModifier = $direction === 'desc' ? '-' : '';

        return $directionModifier.$field;
    }
}
