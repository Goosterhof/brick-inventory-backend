<?php

declare(strict_types=1);

namespace App\Services;

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

    public function getSetParts(string $setNum): Set
    {
        $set = Set::where('set_num', $setNum)->first();

        if ($set && $set->setParts()->exists()) {
            return $set->load(['setParts.part', 'setParts.color']);
        }

        $setData = $this->fetchSet($setNum);
        $set = $this->createOrUpdateSet($setData);

        $parts = $this->fetchAllSetParts($setNum);
        $this->storeSetParts($set, $parts);

        return $set->load(['setParts.part', 'setParts.color']);
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
