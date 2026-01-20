<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DataTransferObjects\SetPartData;
use App\DataTransferObjects\SetPartsResultData;
use App\Services\RebrickableService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;

class SetController extends Controller
{
    public function __construct(
        private readonly RebrickableService $rebrickableService,
    ) {}

    public function parts(string $setNum): JsonResponse
    {
        try {
            $result = $this->rebrickableService->getSetParts($setNum);

            return response()->json($this->formatResponse($result));
        } catch (RequestException $requestException) {
            $status = $requestException->response->status();
            $message = match ($status) {
                404 => 'Set not found',
                401 => 'Invalid API key',
                default => 'Failed to fetch set data',
            };

            return response()->json(['error' => $message], $status);
        }
    }

    private function formatResponse(SetPartsResultData $result): array
    {
        return [
            'set' => [
                'set_num' => $result->set->setNum,
                'name' => $result->set->name,
                'year' => $result->set->year,
                'theme' => $result->set->theme,
                'num_parts' => $result->set->numParts,
                'image_url' => $result->set->imageUrl,
            ],
            'parts' => array_map(fn (SetPartData $part): array => [
                'part_num' => $part->partNum,
                'name' => $part->name,
                'category' => $part->category,
                'image_url' => $part->imageUrl,
                'color' => [
                    'id' => $part->color->id,
                    'name' => $part->color->name,
                    'rgb' => $part->color->rgb,
                    'is_transparent' => $part->color->isTransparent,
                ],
                'quantity' => $part->quantity,
                'is_spare' => $part->isSpare,
                'element_id' => $part->elementId,
            ], $result->parts),
        ];
    }
}
