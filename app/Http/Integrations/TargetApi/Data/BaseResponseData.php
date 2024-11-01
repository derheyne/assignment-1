<?php

declare(strict_types=1);

namespace App\Http\Integrations\TargetApi\Data;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
abstract class BaseResponseData extends Data
{
    public int $currentPage;

    public string $firstPageUrl;

    public ?int $from;

    public int $lastPage;

    public string $lastPageUrl;

    /** @var Collection<int, LinkData> */
    public Collection $links;

    public ?string $nextPageUrl;

    public string $path;

    public int $perPage;

    public ?string $prevPageUrl;

    public ?int $to;

    public int $total;
}
