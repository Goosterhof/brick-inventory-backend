<?php

declare(strict_types=1);

namespace App\Http\Resources;

use BackedEnum;
use DateTimeInterface;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
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
     * @param TModel $model
     */
    abstract public static function from(Model $model): static;

    /**
     * Convert the resource to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [];
        $reflectionClass = new ReflectionClass($this);

        foreach ($reflectionClass->getProperties(ReflectionProperty::IS_PUBLIC) as $reflectionProperty) {
            $value = $reflectionProperty->getValue($this);
            $result[$reflectionProperty->getName()] = $this->transformValue($value);
        }

        return $result;
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

    public function toResponse($request): JsonResponse
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
