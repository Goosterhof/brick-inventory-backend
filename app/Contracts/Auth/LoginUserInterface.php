<?php

declare(strict_types=1);

namespace App\Contracts\Auth;

interface LoginUserInterface
{
    public string $email { get; }

    public string $password { get; }
}
