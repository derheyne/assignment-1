<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Integrations\TargetApi\Concerns;

use App\Http\Integrations\TargetApi\TargetApi;
use Saloon\Http\Faking\MockClient;

trait TargetApiHelpers
{
    /** @return array{0: MockClient, 1: TargetApi} */
    public function createTargetApiConnector(array $mocks = []): array
    {
        $mockClient = new MockClient($mocks);

        $connector = new TargetApi;
        $connector->withMockClient($mockClient);

        return [$mockClient, $connector];
    }
}
