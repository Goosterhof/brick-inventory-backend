<?php

declare(strict_types=1);

namespace App\Http\Requests\StorageOption;

use App\Contracts\StorageOption\UpdateStorageOptionInterface;
use App\Http\Requests\DTOFormRequest;
use Illuminate\Http\Request;

final readonly class UpdateStorageOptionRequest extends DTOFormRequest implements UpdateStorageOptionInterface
{
    public const string NAME = 'name';

    public const string DESCRIPTION = 'description';

    public const string PARENT_ID = 'parent_id';

    public const string ROW = 'row';

    public const string COLUMN = 'column';

    public function __construct(
        public string $name,
        public ?string $description = null,
        public ?int $parentId = null,
        public ?int $row = null,
        public ?int $column = null,
    ) {}

    public static function rules(Request $request): array
    {
        return [
            self::NAME => ['required', 'string', 'max:255'],
            self::DESCRIPTION => ['nullable', 'string'],
            self::PARENT_ID => ['nullable', 'integer', 'exists:storage_options,id'],
            self::ROW => ['nullable', 'integer', 'min:0'],
            self::COLUMN => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected static function toDTO(Request $request): static
    {
        return new self(
            name: $request->string(self::NAME)->toString(),
            description: $request->isNotFilled(self::DESCRIPTION) ? null : $request->string(self::DESCRIPTION)->toString(),
            parentId: $request->isNotFilled(self::PARENT_ID) ? null : $request->integer(self::PARENT_ID),
            row: $request->isNotFilled(self::ROW) ? null : $request->integer(self::ROW),
            column: $request->isNotFilled(self::COLUMN) ? null : $request->integer(self::COLUMN),
        );
    }
}
