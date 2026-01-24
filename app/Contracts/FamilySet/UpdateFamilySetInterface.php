<?php

declare(strict_types=1);

namespace App\Contracts\FamilySet;

use App\Enums\FamilySetStatus;
use DateTimeInterface;

interface UpdateFamilySetInterface
{
    public int $quantity { get; }

    public FamilySetStatus $status { get; }

    public ?DateTimeInterface $purchaseDate { get; }

    public ?string $notes { get; }
}
