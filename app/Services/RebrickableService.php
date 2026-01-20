<?php

declare(strict_types=1);

namespace App\Services;

use App\DataTransferObjects\ColorData;
use App\DataTransferObjects\SetData;
use App\DataTransferObjects\SetPartData;
use App\DataTransferObjects\SetPartsResultData;
use App\Models\Color;
use App\Models\Part;
use App\Models\Set;
use App\Models\SetPart;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class RebrickableService
{
    private string $baseUrl = 'https://rebrickable.com/api/v3';

    private readonly string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.rebrickable.key', '');
    }

    public function getSetParts(string $setNum): SetPartsResultData
    {
        $set = Set::where('set_num', $setNum)->first();

        if (!$set || !$set->setParts()->exists()) {
            $setData = $this->fetchSet($setNum);
            $set = $this->createOrUpdateSet($setData);

            $parts = $this->fetchAllSetParts($setNum);
            $this->storeSetParts($set, $parts);
        }

        $set->load(['setParts.part', 'setParts.color']);

        return $this->toDto($set);
    }

    private function toDto(Set $set): SetPartsResultData
    {
        $setData = new SetData(
            setNum: $set->set_num,
            name: $set->name,
            year: $set->year,
            theme: $set->theme,
            numParts: $set->num_parts,
            imageUrl: $set->image_url,
        );

        $parts = $set->setParts->map(fn (SetPart $setPart): SetPartData => new SetPartData(
            partNum: $setPart->part->part_num,
            name: $setPart->part->name,
            category: $setPart->part->category,
            imageUrl: $setPart->part->image_url,
            color: new ColorData(
                id: $setPart->color->rebrickable_id,
                name: $setPart->color->name,
                rgb: $setPart->color->rgb,
                isTransparent: $setPart->color->is_transparent,
            ),
            quantity: $setPart->quantity,
            isSpare: $setPart->is_spare,
            elementId: $setPart->element_id,
        ))->all();

        return new SetPartsResultData(set: $setData, parts: $parts);
    }

    private function fetchSet(string $setNum): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'key ' . $this->apiKey,
        ])->get(sprintf('%s/lego/sets/%s/', $this->baseUrl, $setNum));

        if ($response->failed()) {
            throw new RequestException($response);
        }

        return $response->json();
    }

    private function fetchAllSetParts(string $setNum): array
    {
        $parts = [];
        $url = sprintf('%s/lego/sets/%s/parts/', $this->baseUrl, $setNum);

        while ($url) {
            $response = Http::withHeaders([
                'Authorization' => 'key ' . $this->apiKey,
            ])->get($url);

            if ($response->failed()) {
                throw new RequestException($response);
            }

            $data = $response->json();
            $parts = array_merge($parts, $data['results']);
            $url = $data['next'];
        }

        return $parts;
    }

    private function createOrUpdateSet(array $data): Set
    {
        return Set::updateOrCreate(
            ['set_num' => $data['set_num']],
            [
                'name' => $data['name'],
                'year' => $data['year'],
                'theme' => $data['theme_id'] ?? null,
                'num_parts' => $data['num_parts'],
                'image_url' => $data['set_img_url'],
            ],
        );
    }

    private function storeSetParts(Set $set, array $partsData): void
    {
        foreach ($partsData as $partData) {
            $color = $this->createOrUpdateColor($partData['color']);
            $part = $this->createOrUpdatePart($partData['part']);

            SetPart::updateOrCreate(
                [
                    'set_id' => $set->id,
                    'part_id' => $part->id,
                    'color_id' => $color->id,
                    'is_spare' => $partData['is_spare'],
                ],
                [
                    'quantity' => $partData['quantity'],
                    'element_id' => $partData['element_id'] ?? null,
                ],
            );
        }
    }

    private function createOrUpdateColor(array $data): Color
    {
        return Color::updateOrCreate(
            ['rebrickable_id' => $data['id']],
            [
                'name' => $data['name'],
                'rgb' => $data['rgb'],
                'is_transparent' => $data['is_trans'],
            ],
        );
    }

    private function createOrUpdatePart(array $data): Part
    {
        return Part::updateOrCreate(
            ['part_num' => $data['part_num']],
            [
                'name' => $data['name'],
                'category' => $data['part_cat_id'] ?? null,
                'image_url' => $data['part_img_url'],
            ],
        );
    }
}
