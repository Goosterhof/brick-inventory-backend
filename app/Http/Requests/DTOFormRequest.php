<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\Request;
use Illuminate\Validation\Factory;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;

abstract readonly class DTOFormRequest
{
    final public static function fromRequest(Request $request, Factory $validationFactory): static
    {
        $validator = $validationFactory->make($request->all(), static::rules($request));

        $validator->validate();

        return static::toDTO($request);
    }

    /**
     * @return array<string, array<int, string|ValidationRule|Enum|Exists|Unique>>
     */
    abstract public static function rules(Request $request): array;

    abstract protected static function toDTO(Request $request): static;
}
