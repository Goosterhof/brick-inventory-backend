<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Exceptions\MissingRelationException;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use JsonSerializable;

/**
 * @template TModel of Model
 */
abstract readonly class ResourceData implements JsonSerializable, Responsable
{
    /**
     * Relations that should be eager-loaded for this resource.
     * Single source of truth for both collection() loading and runtime validation.
     *
     * @var array<int, string>
     */
    public const EAGER_LOAD = [];

    /**
     * Create an instance from a model.
     *
     * @param TModel $model
     */
    abstract public static function from($model): static;

    /**
     * Convert the resource to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        /** @var array<string, mixed> */
        return array_map(
            $this->transformValue(...),
            get_object_vars($this),
        );
    }

    /**
     * Create a collection of resources from a collection of models.
     *
     * @param Collection<int, TModel> $models
     *
     * @return array<int, static>
     */
    public static function collection(Collection $models): array
    {
        $models->loadMissing(static::requiredRelations());

        return $models->map(
            static fn (Model $model): static => static::from($model),
        )->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toResponse($request = null): JsonResponse
    {
        return new JsonResponse($this->toArray());
    }

    /**
     * Create a JSON response with a specific status code.
     */
    public function toResponseWithStatus(int $status): JsonResponse
    {
        return new JsonResponse($this->toArray(), $status);
    }

    /**
     * Get the relationships that should be loaded for this resource.
     * Derived from EAGER_LOAD constant — override the constant, not this method.
     *
     * @return array<int, string>
     */
    protected static function requiredRelations(): array
    {
        return static::EAGER_LOAD;
    }

    /**
     * Validate that required relations are loaded on the model.
     *
     * @param TModel $model
     *
     * @throws MissingRelationException
     */
    protected static function validateRelationsLoaded(Model $model): void
    {
        $missingRelations = array_filter(
            static::requiredRelations(),
            static fn (string $relation): bool => !$model->relationLoaded($relation),
        );

        if ($missingRelations !== []) {
            throw MissingRelationException::forRelations(static::class, array_values($missingRelations));
        }
    }

    /**
     * Transform a value for array output.
     */
    protected function transformValue(mixed $value): mixed
    {
        if ($value instanceof self) {
            return $value->toArray();
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('c');
        }

        if (is_array($value)) {
            return array_map($this->transformValue(...), $value);
        }

        return $value;
    }
}
