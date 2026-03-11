<?php

declare(strict_types=1);

namespace App\Http\Requests\StorageOption;

use App\DataTransferObjects\StorageOption\StorageOptionData;
use App\Models\StorageOption;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

final class StorageOptionRequest extends FormRequest
{
    private const string NAME = 'name';

    private const string DESCRIPTION = 'description';

    private const string PARENT_ID = 'parent_id';

    private const string ROW = 'row';

    private const string COLUMN = 'column';

    /**
     * @return array<string, array<int, string|Closure>>
     */
    public function rules(): array
    {
        return [
            self::NAME => ['required', 'string', 'max:255'],
            self::DESCRIPTION => ['nullable', 'string', 'max:65535'],
            self::PARENT_ID => ['nullable', 'integer', 'exists:storage_options,id', function (string $attribute, mixed $value, Closure $fail): void {
                /** @var User $user */
                $user = $this->user();
                /** @var StorageOption|null $parentOption */
                $parentOption = StorageOption::query()->find($value);

                if ($parentOption !== null && $parentOption->family_id !== $user->family_id) {
                    $fail('The selected parent does not belong to your family.');
                }
            }],
            self::ROW => ['nullable', 'integer', 'min:0'],
            self::COLUMN => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function toDto(): StorageOptionData
    {
        return new StorageOptionData(
            name: $this->safe()->string(self::NAME)->toString(),
            description: $this->isNotFilled(self::DESCRIPTION) ? null : $this->safe()->string(self::DESCRIPTION)->toString(),
            parentId: $this->isNotFilled(self::PARENT_ID) ? null : $this->safe()->integer(self::PARENT_ID),
            row: $this->isNotFilled(self::ROW) ? null : $this->safe()->integer(self::ROW),
            column: $this->isNotFilled(self::COLUMN) ? null : $this->safe()->integer(self::COLUMN),
        );
    }
}
