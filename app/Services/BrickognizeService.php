<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\BrickIdentificationServiceInterface;
use App\Data\Brickognize\BrickognizePredictionData;
use App\Exceptions\BrickognizeApiException;
use Illuminate\Container\Attributes\Config;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

final readonly class BrickognizeService implements BrickIdentificationServiceInterface
{
    private const array PREDICTION_REQUIRED_FIELDS = ['id', 'name', 'type', 'score'];

    public function __construct(
        #[Config('services.brickognize.base_url', 'https://api.brickognize.com')] private string $baseUrl,
    ) {}

    /**
     * Identify a LEGO brick from an uploaded image.
     *
     * @throws BrickognizeApiException
     *
     * @return list<BrickognizePredictionData>
     */
    public function identifyBrick(UploadedFile $uploadedFile): array
    {
        $response = $this->httpClient()
            ->attach('query_image', $uploadedFile->getContent(), $uploadedFile->getClientOriginalName())
            ->post('/predict/');

        if ($response->failed()) {
            throw BrickognizeApiException::fromResponse($response, 'Failed to identify brick');
        }

        $data = $response->json();

        $this->validateResponse($data);

        /** @var array{items: list<array{id: string, name: string, type: string, img_url?: string|null, score: float|int}>} $data */
        $predictions = [];
        foreach ($data['items'] as $item) {
            $predictions[] = new BrickognizePredictionData(
                id: $item['id'],
                name: $item['name'],
                type: $item['type'],
                imageUrl: $item['img_url'] ?? null,
                score: (float) $item['score'],
            );
        }

        return $predictions;
    }

    private function httpClient(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->timeout(30)
            ->retry(3, 100, throw: false);
    }

    /**
     * @throws BrickognizeApiException
     */
    private function validateResponse(mixed $data): void
    {
        if (!is_array($data)) {
            throw BrickognizeApiException::invalidResponse('Expected array response');
        }

        if (!array_key_exists('items', $data) || !is_array($data['items'])) {
            throw BrickognizeApiException::invalidResponse("Missing or invalid 'items' field");
        }

        foreach ($data['items'] as $index => $item) {
            $this->validatePredictionItem($item, $index);
        }
    }

    /**
     * @throws BrickognizeApiException
     */
    private function validatePredictionItem(mixed $item, int $index): void
    {
        if (!is_array($item)) {
            throw BrickognizeApiException::invalidResponse(
                sprintf('Prediction at index %d is not an array', $index),
            );
        }

        $missingFields = [];
        foreach (self::PREDICTION_REQUIRED_FIELDS as $field) {
            if (!array_key_exists($field, $item)) {
                $missingFields[] = $field;
            }
        }

        if ($missingFields !== []) {
            throw BrickognizeApiException::invalidResponse(
                sprintf('Prediction at index %d missing fields: %s', $index, implode(', ', $missingFields)),
            );
        }
    }
}
