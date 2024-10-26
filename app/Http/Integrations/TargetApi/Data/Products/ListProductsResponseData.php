<?php

declare(strict_types=1);

namespace App\Http\Integrations\TargetApi\Data\Products;

use App\Http\Integrations\TargetApi\Data\BaseResponseData;
use App\Http\Integrations\TargetApi\Data\Objects\ProductObjectData;
use Illuminate\Support\Collection;

class ListProductsResponseData extends BaseResponseData
{
    /** @var Collection<int, ProductObjectData> */
    public Collection $data;
}
