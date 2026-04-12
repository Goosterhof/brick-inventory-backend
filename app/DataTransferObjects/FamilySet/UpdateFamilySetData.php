<?php

declare(strict_types = 1);

namespace App\DataTransferObjects\FamilySet;

use App\Enums\FamilySetStatus;
use DateTimeInterface;

final readonly class UpdateFamilySetData
{
    public function __construct(
        public int $quantity,
        public FamilySetStatus $status,
        public ?DateTimeInterface $purchaseDate = null,
        public ?string $notes = null,
    ) {}
}
