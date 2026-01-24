<?php

declare(strict_types=1);

namespace App\Actions;

use App\Contracts\RebrickableServiceInterface;
use App\DataTransferObjects\SetPartsResultData;

class GetSetPartsAction
{
    public function __construct(
        private readonly RebrickableServiceInterface $rebrickableService,
    ) {}

    public function execute(string $setNum): SetPartsResultData
    {
        return $this->rebrickableService->getSetParts($setNum);
    }
}
