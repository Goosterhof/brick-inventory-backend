<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GetSetByEanAction;
use App\Actions\GetSetPartsAction;
use App\Http\Resources\SetSummaryResourceData;
use App\Http\Resources\SetWithPartsResourceData;
use Illuminate\Http\JsonResponse;

class SetController extends Controller
{
    public function parts(string $setNum, GetSetPartsAction $getSetPartsAction): JsonResponse
    {
        $set = $getSetPartsAction->execute($setNum);

        return SetWithPartsResourceData::from($set)->toResponse();
    }

    public function lookupByEan(string $ean, GetSetByEanAction $getSetByEanAction): JsonResponse
    {
        $set = $getSetByEanAction->execute($ean);

        return SetSummaryResourceData::from($set)->toResponse();
    }
}
