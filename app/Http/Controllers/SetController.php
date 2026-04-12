<?php

declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Actions\GetSetByEanAction;
use App\Actions\GetSetPartsAction;
use App\Actions\GetSetStorageMapAction;
use App\Http\Resources\SetSummaryResourceData;
use App\Http\Resources\SetWithPartsResourceData;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
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

    public function storageMap(
        string $setNum,
        GetSetPartsAction $getSetPartsAction,
        GetSetStorageMapAction $getSetStorageMapAction,
        #[CurrentUser]
        User $user,
    ): JsonResponse {
        $set = $getSetPartsAction->execute($setNum);

        return new JsonResponse($getSetStorageMapAction->execute($set, $user->family));
    }
}
