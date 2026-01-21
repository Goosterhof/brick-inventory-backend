<?php

declare(strict_types=1);

namespace App\Actions;

use App\DataTransferObjects\SetPartsResultData;
use App\Services\RebrickableService;

class GetSetPartsAction
{
    public function __construct(
        private readonly RebrickableService $rebrickableService,
    ) {}

    public function execute(string $setNum): SetPartsResultData
    {
        return $this->rebrickableService->getSetParts($setNum);
    }
}
