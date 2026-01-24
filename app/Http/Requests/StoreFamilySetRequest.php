<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Contracts\FamilySet\CreateFamilySetInterface;
use App\Enums\FamilySetStatus;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final readonly class StoreFamilySetRequest extends DTOFormRequest implements CreateFamilySetInterface
{
    public const string SET_NUM = 'set_num';

    public const string QUANTITY = 'quantity';

    public const string STATUS = 'status';

    public const string PURCHASE_DATE = 'purchase_date';

    public const string NOTES = 'notes';

    public function __construct(
        public string $setNum,
        public int $quantity,
        public FamilySetStatus $status,
        public ?DateTimeInterface $purchaseDate = null,
        public ?string $notes = null,
    ) {}

    public static function rules(Request $request): array
    {
        return [
            self::SET_NUM => ['required', 'string', 'max:255'],
            self::QUANTITY => ['sometimes', 'integer', 'min:1'],
            self::STATUS => ['sometimes', 'string', Rule::enum(FamilySetStatus::class)],
            self::PURCHASE_DATE => ['sometimes', 'nullable', 'date'],
            self::NOTES => ['sometimes', 'nullable', 'string', 'max:65535'],
        ];
    }

    protected static function toDTO(Request $request): static
    {
        return new self(
            setNum: $request->string(self::SET_NUM)->toString(),
            quantity: $request->isNotFilled(self::QUANTITY) ? 1 : $request->integer(self::QUANTITY),
            status: $request->isNotFilled(self::STATUS)
                ? FamilySetStatus::Sealed
                : FamilySetStatus::from($request->string(self::STATUS)->toString()),
            purchaseDate: $request->isNotFilled(self::PURCHASE_DATE)
                ? null
                : CarbonImmutable::parse($request->string(self::PURCHASE_DATE)->toString()),
            notes: $request->isNotFilled(self::NOTES) ? null : $request->string(self::NOTES)->toString(),
        );
    }
}
