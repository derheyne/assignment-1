<?php
declare(strict_types=1);

namespace Tests\Feature\Http\Integrations\SourceApi\Concerns;

use App\Http\Integrations\SourceApi\SourceApi;
use Saloon\Http\Faking\MockClient;

trait SourceApiHelpers
{
    /** @return array{0: MockClient, 1: SourceApi} */
    public function createSourceApiConnector(array $mocks = []): array
    {
        $mockClient = new MockClient($mocks);

        $connector = new SourceApi;
        $connector->withMockClient($mockClient);

        return [$mockClient, $connector];
    }
}
