<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Contracts\FamilySet\UpdateFamilySetInterface;
use App\Enums\FamilySetStatus;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final readonly class UpdateFamilySetRequest extends DTOFormRequest implements UpdateFamilySetInterface
{
    public const string QUANTITY = 'quantity';

    public const string STATUS = 'status';

    public const string PURCHASE_DATE = 'purchase_date';

    public const string NOTES = 'notes';

    public function __construct(
        public int $quantity,
        public FamilySetStatus $status,
        public ?DateTimeInterface $purchaseDate = null,
        public ?string $notes = null,
    ) {}

    public static function rules(Request $request): array
    {
        return [
            self::QUANTITY => ['required', 'integer', 'min:1'],
            self::STATUS => ['required', 'string', Rule::enum(FamilySetStatus::class)],
            self::PURCHASE_DATE => ['nullable', 'date'],
            self::NOTES => ['nullable', 'string', 'max:65535'],
        ];
    }

    protected static function toDTO(Request $request): static
    {
        return new self(
            quantity: $request->integer(self::QUANTITY),
            status: FamilySetStatus::from($request->string(self::STATUS)->toString()),
            purchaseDate: $request->isNotFilled(self::PURCHASE_DATE)
                ? null
                : CarbonImmutable::parse($request->string(self::PURCHASE_DATE)->toString()),
            notes: $request->isNotFilled(self::NOTES) ? null : $request->string(self::NOTES)->toString(),
        );
    }
}
