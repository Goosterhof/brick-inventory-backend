<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Enums\FamilySetStatus;
use DateTimeInterface;

final readonly class UpdateFamilySetData
{
    public function __construct(
        public ?int $quantity = null,
        public ?FamilySetStatus $status = null,
        public ?DateTimeInterface $purchaseDate = null,
        public ?string $notes = null,
    ) {}
}
