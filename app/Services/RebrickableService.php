<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Sync\StoreSetPartsAction;
use App\Actions\Sync\UpsertSetAction;
use App\Contracts\LegoDataServiceInterface;
use App\Data\Lego\LegoSetData;
use App\Data\Lego\LegoSetPartData;
use App\Exceptions\InvalidApiResponseException;
use App\Exceptions\RebrickableApiException;
use App\Exceptions\SetNotFoundException;
use App\Models\Set;
use Illuminate\Container\Attributes\Config;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

final readonly class RebrickableService implements LegoDataServiceInterface
{
    private const array SET_REQUIRED_FIELDS = ['set_num', 'name', 'year', 'num_parts'];

    private const array PART_REQUIRED_FIELDS = ['part', 'color', 'quantity', 'is_spare'];

    private const array PART_NESTED_REQUIRED_FIELDS = ['part_num', 'name'];

    private const array COLOR_NESTED_REQUIRED_FIELDS = ['id', 'name', 'rgb', 'is_trans'];

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
     * @throws SetNotFoundException
     * @throws RebrickableApiException
     * @throws InvalidApiResponseException
     */
    public function fetchSet(string $setNum): LegoSetData
    {
        $response = $this->httpClient()->get(sprintf('/lego/sets/%s/', $setNum));

        $this->handleErrorResponse($response, $setNum);

        $data = $response->json();

        $this->validateSetResponse($data, $setNum);

        /** @var array{set_num: string, name: string, year: int, theme_id: int|null, num_parts: int, set_img_url: string|null} $data */
        return LegoSetData::fromArray($data);
    }

    /**
     * @return list<LegoSetPartData>
     *
     * @throws RebrickableApiException
     * @throws InvalidApiResponseException
     */
    public function fetchSetParts(string $setNum): array
    {
        /** @var list<LegoSetPartData> $parts */
        $parts = [];
        $nextUrl = sprintf('/lego/sets/%s/parts/', $setNum);

        do {
            $response = $this->httpClient()->get($nextUrl);

            if ($response->failed()) {
                throw RebrickableApiException::fromResponse($response, sprintf("Failed to fetch parts for set '%s'", $setNum));
            }

            /** @var array{results: list<array{part: array{part_num: string, name: string, part_cat_id: int|null, part_img_url: string|null}, color: array{id: int, name: string, rgb: string, is_trans: bool}, quantity: int, is_spare: bool, element_id: string|null}>, next: string|null} $data */
            $data = $response->json();

            $this->validatePartsResponse($data, $setNum);

            foreach ($data['results'] as $partData) {
                $parts[] = LegoSetPartData::fromArray($partData);
            }

            $nextUrl = $data['next'];
        } while (is_string($nextUrl));

        return $parts;
    }

    private function httpClient(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withHeaders(['Authorization' => 'key ' . $this->apiKey])
            ->timeout(30)
            ->retry(3, 100, throw: false);
    }

    /**
     * @throws SetNotFoundException
     * @throws RebrickableApiException
     */
    private function handleErrorResponse(Response $response, string $setNum): void
    {
        if ($response->successful()) {
            return;
        }

        if ($response->status() === 404) {
            throw SetNotFoundException::forSetNum($setNum);
        }

        throw RebrickableApiException::fromResponse($response, sprintf("Failed to fetch set '%s'", $setNum));
    }

    /**
     * @throws InvalidApiResponseException
     */
    private function validateSetResponse(mixed $data, string $setNum): void
    {
        if (!is_array($data)) {
            throw InvalidApiResponseException::invalidStructure(
                sprintf("Fetching set '%s'", $setNum),
                'Expected array response',
            );
        }

        $missingFields = [];
        foreach (self::SET_REQUIRED_FIELDS as $field) {
            if (!array_key_exists($field, $data)) {
                $missingFields[] = $field;
            }
        }

        if ($missingFields !== []) {
            throw InvalidApiResponseException::missingFields($missingFields, sprintf("Fetching set '%s'", $setNum));
        }
    }

    /**
     * @throws InvalidApiResponseException
     */
    private function validatePartsResponse(mixed $data, string $setNum): void
    {
        if (!is_array($data)) {
            throw InvalidApiResponseException::invalidStructure(
                sprintf("Fetching parts for set '%s'", $setNum),
                'Expected array response',
            );
        }

        if (!array_key_exists('results', $data) || !is_array($data['results'])) {
            throw InvalidApiResponseException::invalidStructure(
                sprintf("Fetching parts for set '%s'", $setNum),
                "Missing or invalid 'results' field",
            );
        }

        foreach ($data['results'] as $index => $partData) {
            $this->validatePartData($partData, $setNum, $index);
        }
    }

    /**
     * @throws InvalidApiResponseException
     */
    private function validatePartData(mixed $partData, string $setNum, int $index): void
    {
        if (!is_array($partData)) {
            throw InvalidApiResponseException::invalidStructure(
                sprintf("Fetching parts for set '%s'", $setNum),
                sprintf('Part at index %d is not an array', $index),
            );
        }

        $missingFields = [];
        foreach (self::PART_REQUIRED_FIELDS as $field) {
            if (!array_key_exists($field, $partData)) {
                $missingFields[] = $field;
            }
        }

        if ($missingFields !== []) {
            throw InvalidApiResponseException::missingFields(
                $missingFields,
                sprintf("Part at index %d for set '%s'", $index, $setNum),
            );
        }

        $this->validateNestedPartData($partData['part'], $setNum, $index);
        $this->validateNestedColorData($partData['color'], $setNum, $index);
    }

    /**
     * @throws InvalidApiResponseException
     */
    private function validateNestedPartData(mixed $partData, string $setNum, int $index): void
    {
        if (!is_array($partData)) {
            throw InvalidApiResponseException::invalidStructure(
                sprintf("Part at index %d for set '%s'", $index, $setNum),
                "'part' field is not an array",
            );
        }

        $missingFields = [];
        foreach (self::PART_NESTED_REQUIRED_FIELDS as $field) {
            if (!array_key_exists($field, $partData)) {
                $missingFields[] = 'part.' . $field;
            }
        }

        if ($missingFields !== []) {
            throw InvalidApiResponseException::missingFields(
                $missingFields,
                sprintf("Part at index %d for set '%s'", $index, $setNum),
            );
        }
    }

    /**
     * @throws InvalidApiResponseException
     */
    private function validateNestedColorData(mixed $colorData, string $setNum, int $index): void
    {
        if (!is_array($colorData)) {
            throw InvalidApiResponseException::invalidStructure(
                sprintf("Part at index %d for set '%s'", $index, $setNum),
                "'color' field is not an array",
            );
        }

        $missingFields = [];
        foreach (self::COLOR_NESTED_REQUIRED_FIELDS as $field) {
            if (!array_key_exists($field, $colorData)) {
                $missingFields[] = 'color.' . $field;
            }
        }

        if ($missingFields !== []) {
            throw InvalidApiResponseException::missingFields(
                $missingFields,
                sprintf("Part at index %d for set '%s'", $index, $setNum),
            );
        }
    }
}
