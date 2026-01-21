<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Enums\FamilySetStatus;
use DateTimeInterface;

final readonly class CreateFamilySetData
{
    public function __construct(
        public string $setNum,
        public int $quantity = 1,
        public FamilySetStatus $status = FamilySetStatus::Sealed,
        public ?DateTimeInterface $purchaseDate = null,
        public ?string $notes = null,
    ) {}
}
