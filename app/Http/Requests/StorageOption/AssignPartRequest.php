<?php

declare(strict_types=1);

namespace App\Http\Requests\StorageOption;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AssignPartRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'part_id' => ['required', 'integer', 'exists:parts,id'],
            'color_id' => ['nullable', 'integer', 'exists:colors,id'],
            'quantity' => ['required', 'integer', 'min:0'],
        ];
    }
}
