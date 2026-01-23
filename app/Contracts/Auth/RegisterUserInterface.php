<?php

declare(strict_types=1);

namespace App\Contracts\Auth;

interface RegisterUserInterface
{
    public string $familyName { get; }

    public string $name { get; }

    public string $email { get; }

    public string $password { get; }
}
