<?php

declare(strict_types=1);

namespace App\Http\Requests\BrickIdentification;

use Illuminate\Foundation\Http\FormRequest;

final class IdentifyBrickRequest extends FormRequest
{
    public const string IMAGE = 'image';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            self::IMAGE => ['required', 'image', 'max:10240'], // Max 10MB
        ];
    }
}
