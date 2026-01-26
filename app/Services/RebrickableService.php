<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\LegoDataServiceInterface;
use App\Models\Color;
use App\Models\Part;
use App\Models\Set;
use App\Models\SetPart;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

final class RebrickableService implements LegoDataServiceInterface
{
    private string $baseUrl = 'https://rebrickable.com/api/v3';

    private readonly string $apiKey;

    public function __construct(
        private readonly Set $set,
        private readonly Part $part,
        private readonly Color $color,
        private readonly SetPart $setPart,
    ) {
        $apiKey = config('services.rebrickable.key');
        $this->apiKey = is_string($apiKey) ? $apiKey : '';
    }

    public function getSetParts(string $setNum): Set
    {
        $set = $this->set->newQuery()->where('set_num', $setNum)->first();

        if (!$set instanceof Set || !$set->setParts()->exists()) {
            $setData = $this->fetchSet($setNum);
            $set = $this->createOrUpdateSet($setData);

            $parts = $this->fetchAllSetParts($setNum);
            $this->storeSetParts($set, $parts);
        }

        $set->load(['setParts.part', 'setParts.color']);

        return $set;
    }

    /**
     * @return array{set_num: string, name: string, year: int, theme_id: int|null, num_parts: int, set_img_url: string|null}
     */
    public function fetchSet(string $setNum): array
    {
        $response = $this->httpClient()->get(sprintf('%s/lego/sets/%s/', $this->baseUrl, $setNum));

        throw_if($response->failed(), RequestException::class, $response);

        /** @var array{set_num: string, name: string, year: int, theme_id: int|null, num_parts: int, set_img_url: string|null} */
        return $response->json();
    }

    private function httpClient(): PendingRequest
    {
        return Http::withHeaders([
            'Authorization' => 'key ' . $this->apiKey,
        ]);
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
            $response = $this->httpClient()->get($url);

            throw_if($response->failed(), RequestException::class, $response);

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
        $set = $this->set->newQuery()->where('set_num', $data['set_num'])->first();

        if (!$set instanceof Set) {
            /** @var Set $set */
            $set = $this->set->newInstance();
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

            $setPart = $this->setPart->newQuery()
                ->where('set_id', $set->id)
                ->where('part_id', $part->id)
                ->where('color_id', $color->id)
                ->where('is_spare', $partData['is_spare'])
                ->first();

            if (!$setPart instanceof SetPart) {
                /** @var SetPart $setPart */
                $setPart = $this->setPart->newInstance();
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
        $color = $this->color->newQuery()->where('rebrickable_id', $data['id'])->first();

        if (!$color instanceof Color) {
            /** @var Color $color */
            $color = $this->color->newInstance();
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
        $part = $this->part->newQuery()->where('part_num', $data['part_num'])->first();

        if (!$part instanceof Part) {
            /** @var Part $part */
            $part = $this->part->newInstance();
            $part->part_num = $data['part_num'];
        }

        $part->name = $data['name'];
        $part->category = $data['part_cat_id'] !== null ? (string) $data['part_cat_id'] : null;
        $part->image_url = $data['part_img_url'];
        $part->save();

        return $part;
    }
}
