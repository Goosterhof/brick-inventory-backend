<?php

declare(strict_types=1);

namespace App\Http\Requests\StorageOption;

use App\Contracts\StorageOption\AssignPartToStorageInterface;
use App\Http\Requests\DTOFormRequest;
use Illuminate\Http\Request;

final readonly class AssignPartRequest extends DTOFormRequest implements AssignPartToStorageInterface
{
    public const string PART_ID = 'part_id';

    public const string COLOR_ID = 'color_id';

    public const string QUANTITY = 'quantity';

    public function __construct(
        public int $partId,
        public int $quantity,
        public ?int $colorId = null,
    ) {}

    public static function rules(Request $request): array
    {
        return [
            self::PART_ID => ['required', 'integer', 'exists:parts,id'],
            self::COLOR_ID => ['nullable', 'integer', 'exists:colors,id'],
            self::QUANTITY => ['required', 'integer', 'min:0'],
        ];
    }

    protected static function toDTO(Request $request): static
    {
        return new self(
            partId: $request->integer(self::PART_ID),
            quantity: $request->integer(self::QUANTITY),
            colorId: $request->isNotFilled(self::COLOR_ID) ? null : $request->integer(self::COLOR_ID),
        );
    }
}
