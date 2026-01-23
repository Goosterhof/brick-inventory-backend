<?php

declare(strict_types=1);

namespace App\Contracts\StorageOption;

interface UpdateStorageOptionInterface
{
    public string $name { get; }

    public ?string $description { get; }

    public ?int $parentId { get; }

    public ?int $row { get; }

    public ?int $column { get; }
}
