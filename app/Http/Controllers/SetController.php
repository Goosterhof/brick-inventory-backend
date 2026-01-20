<?php

declare(strict_types=1);

namespace App\Http\Controllers;

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
            $set = $this->rebrickableService->getSetParts($setNum);

            return response()->json([
                'set' => [
                    'set_num' => $set->set_num,
                    'name' => $set->name,
                    'year' => $set->year,
                    'theme' => $set->theme,
                    'num_parts' => $set->num_parts,
                    'image_url' => $set->image_url,
                ],
                'parts' => $set->setParts->map(fn ($setPart): array => [
                    'part_num' => $setPart->part->part_num,
                    'name' => $setPart->part->name,
                    'category' => $setPart->part->category,
                    'image_url' => $setPart->part->image_url,
                    'color' => [
                        'id' => $setPart->color->rebrickable_id,
                        'name' => $setPart->color->name,
                        'rgb' => $setPart->color->rgb,
                        'is_transparent' => $setPart->color->is_transparent,
                    ],
                    'quantity' => $setPart->quantity,
                    'is_spare' => $setPart->is_spare,
                    'element_id' => $setPart->element_id,
                ]),
            ]);
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
}
