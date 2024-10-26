<?php

declare(strict_types=1);

namespace App\Http\Integrations\SourceApi\Data\ProductFeed;

use App\Http\Integrations\SourceApi\Data\BaseResponseData;
use App\Http\Integrations\SourceApi\Data\Objects\ProductObjectData;
use Illuminate\Support\Collection;

class ProductFeedResponseData extends BaseResponseData
{
    /** @var Collection<int, ProductObjectData> */
    public Collection $data;
}
