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
use RuntimeException;

class RebrickableService
{
    private string $baseUrl = 'https://rebrickable.com/api/v3';

    private readonly string $apiKey;

    public function __construct()
    {
        $apiKey = config('services.rebrickable.key');
        $this->apiKey = is_string($apiKey) ? $apiKey : '';
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

    /**
     * @return array{set_num: string, name: string, year: int, theme_id: int|null, num_parts: int, set_img_url: string|null}
     */
    public function fetchSet(string $setNum): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'key ' . $this->apiKey,
        ])->get(sprintf('%s/lego/sets/%s/', $this->baseUrl, $setNum));

        if ($response->failed()) {
            throw new RequestException($response);
        }

        /** @var array{set_num: string, name: string, year: int, theme_id: int|null, num_parts: int, set_img_url: string|null} */
        return $response->json();
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

        $parts = $set->setParts->map(function (SetPart $setPart): SetPartData {
            $part = $setPart->part;
            $color = $setPart->color;

            if ($part === null || $color === null) {
                throw new RuntimeException('SetPart is missing required relationships');
            }

            return new SetPartData(
                partNum: $part->part_num,
                name: $part->name,
                category: $part->category,
                imageUrl: $part->image_url,
                color: new ColorData(
                    id: $color->rebrickable_id,
                    name: $color->name,
                    rgb: $color->rgb,
                    isTransparent: $color->is_transparent,
                ),
                quantity: $setPart->quantity,
                isSpare: $setPart->is_spare,
                elementId: $setPart->element_id,
            );
        })->all();

        return new SetPartsResultData(set: $setData, parts: $parts);
    }

    /**
     * @return list<array{part: array{part_num: string, name: string, part_cat_id: int|null, part_img_url: string|null}, color: array{id: int, name: string, rgb: string, is_trans: bool}, quantity: int, is_spare: bool, element_id: string|null}>
     */
    private function fetchAllSetParts(string $setNum): array
    {
        /** @var list<array{part: array{part_num: string, name: string, part_cat_id: int|null, part_img_url: string|null}, color: array{id: int, name: string, rgb: string, is_trans: bool}, quantity: int, is_spare: bool, element_id: string|null}> $parts */
        $parts = [];
        $url = sprintf('%s/lego/sets/%s/parts/', $this->baseUrl, $setNum);

        do {
            $response = Http::withHeaders([
                'Authorization' => 'key ' . $this->apiKey,
            ])->get($url);

            if ($response->failed()) {
                throw new RequestException($response);
            }

            /** @var array{results: list<array{part: array{part_num: string, name: string, part_cat_id: int|null, part_img_url: string|null}, color: array{id: int, name: string, rgb: string, is_trans: bool}, quantity: int, is_spare: bool, element_id: string|null}>, next: string|null} $data */
            $data = $response->json();
            $parts = array_merge($parts, $data['results']);
            $url = $data['next'];
        } while ($url !== null);

        return $parts;
    }

    /**
     * @param  array{set_num: string, name: string, year: int, theme_id: int|null, num_parts: int, set_img_url: string|null}  $data
     */
    private function createOrUpdateSet(array $data): Set
    {
        $set = Set::where('set_num', $data['set_num'])->first();

        if (!$set) {
            $set = new Set;
            $set->set_num = $data['set_num'];
        }

        $set->name = $data['name'];
        $set->year = $data['year'];
        $set->theme = $data['theme_id'] !== null ? (string) $data['theme_id'] : null;
        $set->num_parts = $data['num_parts'];
        $set->image_url = $data['set_img_url'];
        $set->save();

        return $set;
    }

    /**
     * @param  list<array{part: array{part_num: string, name: string, part_cat_id: int|null, part_img_url: string|null}, color: array{id: int, name: string, rgb: string, is_trans: bool}, quantity: int, is_spare: bool, element_id: string|null}>  $partsData
     */
    private function storeSetParts(Set $set, array $partsData): void
    {
        foreach ($partsData as $partData) {
            $color = $this->createOrUpdateColor($partData['color']);
            $part = $this->createOrUpdatePart($partData['part']);

            $setPart = SetPart::where('set_id', $set->id)
                ->where('part_id', $part->id)
                ->where('color_id', $color->id)
                ->where('is_spare', $partData['is_spare'])
                ->first();

            if (!$setPart) {
                $setPart = new SetPart;
                $setPart->set_id = $set->id;
                $setPart->part_id = $part->id;
                $setPart->color_id = $color->id;
                $setPart->is_spare = $partData['is_spare'];
            }

            $setPart->quantity = $partData['quantity'];
            $setPart->element_id = $partData['element_id'];
            $setPart->save();
        }
    }

    /**
     * @param  array{id: int, name: string, rgb: string, is_trans: bool}  $data
     */
    private function createOrUpdateColor(array $data): Color
    {
        $color = Color::where('rebrickable_id', $data['id'])->first();

        if (!$color) {
            $color = new Color;
            $color->rebrickable_id = $data['id'];
        }

        $color->name = $data['name'];
        $color->rgb = $data['rgb'];
        $color->is_transparent = $data['is_trans'];
        $color->save();

        return $color;
    }

    /**
     * @param  array{part_num: string, name: string, part_cat_id: int|null, part_img_url: string|null}  $data
     */
    private function createOrUpdatePart(array $data): Part
    {
        $part = Part::where('part_num', $data['part_num'])->first();

        if (!$part) {
            $part = new Part;
            $part->part_num = $data['part_num'];
        }

        $part->name = $data['name'];
        $part->category = $data['part_cat_id'] !== null ? (string) $data['part_cat_id'] : null;
        $part->image_url = $data['part_img_url'];
        $part->save();

        return $part;
    }
}
