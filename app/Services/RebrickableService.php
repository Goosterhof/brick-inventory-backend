<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Sync\StoreSetPartsAction;
use App\Actions\Sync\UpsertSetAction;
use App\Contracts\LegoDataServiceInterface;
use App\Models\Set;
use Illuminate\Container\Attributes\Config;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

final readonly class RebrickableService implements LegoDataServiceInterface
{
    public function __construct(
        #[Config('services.rebrickable.key', '')] private string $apiKey,
        #[Config('services.rebrickable.base_url', 'https://rebrickable.com/api/v3')] private string $baseUrl,
        private Set $set,
        private UpsertSetAction $upsertSetAction,
        private StoreSetPartsAction $storeSetPartsAction,
    ) {}

    public function getSetParts(string $setNum): Set
    {
        $set = $this->set->newQuery()->where('set_num', $setNum)->first();

        if (!$set instanceof Set || !$set->setParts()->exists()) {
            $setData = $this->fetchSet($setNum);
            $set = $this->upsertSetAction->execute($setData);

            $parts = $this->fetchSetParts($setNum);
            $this->storeSetPartsAction->execute($set, $parts);
        }

        $set->load(['setParts.part', 'setParts.color']);

        return $set;
    }

    /**
     * @return array{set_num: string, name: string, year: int, theme_id: int|null, num_parts: int, set_img_url: string|null}
     */
    public function fetchSet(string $setNum): array
    {
        $response = $this->httpClient()->get(sprintf('/lego/sets/%s/', $setNum));

        if ($response->failed()) {
            throw new RequestException($response);
        }

        /** @var array{set_num: string, name: string, year: int, theme_id: int|null, num_parts: int, set_img_url: string|null} */
        return $response->json();
    }

    /**
     * @return list<array{part: array{part_num: string, name: string, part_cat_id: int|null, part_img_url: string|null}, color: array{id: int, name: string, rgb: string, is_trans: bool}, quantity: int, is_spare: bool, element_id: string|null}>
     */
    public function fetchSetParts(string $setNum): array
    {
        /** @var list<array{part: array{part_num: string, name: string, part_cat_id: int|null, part_img_url: string|null}, color: array{id: int, name: string, rgb: string, is_trans: bool}, quantity: int, is_spare: bool, element_id: string|null}> $parts */
        $parts = [];
        $url = sprintf('/lego/sets/%s/parts/', $setNum);

        do {
            $response = $this->httpClient()->get($url);

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

    private function httpClient(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withHeaders(['Authorization' => 'key ' . $this->apiKey])
            ->timeout(30)
            ->retry(3, 100);
    }
}
