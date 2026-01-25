<?php

declare(strict_types=1);

namespace App\Actions;

use App\Contracts\LegoDataServiceInterface;
use App\DataTransferObjects\SetPartsResultData;

class GetSetPartsAction
{
    public function __construct(
        private readonly LegoDataServiceInterface $legoDataService,
    ) {}

    public function execute(string $setNum): SetPartsResultData
    {
        return $this->legoDataService->getSetParts($setNum);
    }
}
