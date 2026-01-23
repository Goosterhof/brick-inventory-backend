<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Contracts\Auth\RegisterUserInterface;
use App\Http\Requests\DTOFormRequest;
use Illuminate\Http\Request;

final readonly class RegisterRequest extends DTOFormRequest implements RegisterUserInterface
{
    public const string FAMILY_NAME = 'family_name';

    public const string NAME = 'name';

    public const string EMAIL = 'email';

    public const string PASSWORD = 'password';

    public function __construct(
        public string $familyName,
        public string $name,
        public string $email,
        public string $password,
    ) {}

    public static function rules(Request $request): array
    {
        return [
            self::FAMILY_NAME => ['required', 'string', 'max:255'],
            self::NAME => ['required', 'string', 'max:255'],
            self::EMAIL => ['required', 'string', 'email', 'max:255', 'unique:users'],
            self::PASSWORD => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    protected static function toDTO(Request $request): static
    {
        return new self(
            familyName: $request->string(self::FAMILY_NAME)->toString(),
            name: $request->string(self::NAME)->toString(),
            email: $request->string(self::EMAIL)->toString(),
            password: $request->string(self::PASSWORD)->toString(),
        );
    }
}
