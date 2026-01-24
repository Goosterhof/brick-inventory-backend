<?php

declare(strict_types=1);

namespace App\Contracts\FamilySet;

interface CreateFamilySetInterface extends UpdateFamilySetInterface
{
    public string $setNum { get; }
}
