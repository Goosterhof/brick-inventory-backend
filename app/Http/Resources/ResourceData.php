<?php

declare(strict_types=1);

namespace App\Http\Resources;

use BackedEnum;
use DateTimeInterface;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;

/**
 * @template TModel of Model
 */
abstract readonly class ResourceData implements JsonSerializable, Responsable
{
    /**
     * Create an instance from a model.
     *
     * @param  TModel  $model
     */
    abstract public static function from(Model $model): static;

    /**
     * Convert the resource to an array with snake_case keys.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [];
        $reflection = new ReflectionClass($this);

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $value = $property->getValue($this);

            if ($value instanceof MissingValue) {
                continue;
            }

            $key = Str::snake($property->getName());
            $result[$key] = $this->transformValue($value);
        }

        return $result;
    }

    /**
     * Create a collection of resources from an iterable of models.
     *
     * @param  iterable<TModel>  $models
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function collection(iterable $models): array
    {
        $items = $models instanceof Collection ? $models->all() : $models;

        return ['data' => array_values(array_map(
            /** @phpstan-ignore-next-line */
            static fn (Model $model): array => static::from($model)->toArray(),
            is_array($items) ? $items : iterator_to_array($items),
        ))];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toResponse($request): JsonResponse
    {
        return new JsonResponse(['data' => $this->toArray()]);
    }

    /**
     * Create a JSON response with a specific status code.
     */
    public function toResponseWithStatus(int $status): JsonResponse
    {
        return new JsonResponse(['data' => $this->toArray()], $status);
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

    /**
     * Helper to handle conditionally loaded relations.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T|MissingValue
     */
    protected static function whenLoaded(Model $model, string $relation, callable $callback): mixed
    {
        if (!$model->relationLoaded($relation)) {
            return new MissingValue;
        }

        return $callback();
    }
}
