<?php

declare(strict_types=1);

namespace App\Contracts\StorageOption;

interface AssignPartToStorageInterface
{
    public int $partId { get; }

    public ?int $colorId { get; }

    public int $quantity { get; }
}
