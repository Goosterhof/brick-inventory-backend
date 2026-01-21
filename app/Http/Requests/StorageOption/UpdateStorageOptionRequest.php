<?php

declare(strict_types=1);

namespace App\Http\Requests\StorageOption;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStorageOptionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'integer', 'exists:storage_options,id'],
            'row' => ['nullable', 'integer', 'min:0'],
            'column' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
