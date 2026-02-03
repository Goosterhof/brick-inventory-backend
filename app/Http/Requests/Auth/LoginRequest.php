<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Contracts\Auth\LoginUserInterface;
use App\Http\Requests\DTOFormRequest;
use Illuminate\Http\Request;

final readonly class LoginRequest extends DTOFormRequest implements LoginUserInterface
{
    public const string EMAIL = 'email';

    public const string PASSWORD = 'password';

    public function __construct(
        public string $email,
        public string $password,
    ) {}

    public static function rules(Request $request): array
    {
        return [
            self::EMAIL => ['required', 'string', 'email'],
            self::PASSWORD => ['required', 'string'],
        ];
    }

    protected static function toDTO(Request $request): static
    {
        return new self(
            email: $request->string(self::EMAIL)->toString(),
            password: $request->string(self::PASSWORD)->toString(),
        );
    }
}
