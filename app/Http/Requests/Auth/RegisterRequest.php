<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\DataTransferObjects\Auth\RegisterUserData;
use Illuminate\Foundation\Http\FormRequest;

final class RegisterRequest extends FormRequest
{
    public const string FAMILY_NAME = 'family_name';

    public const string NAME = 'name';

    public const string EMAIL = 'email';

    public const string PASSWORD = 'password';

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            self::FAMILY_NAME => ['required', 'string', 'max:255'],
            self::NAME => ['required', 'string', 'max:255'],
            self::EMAIL => ['required', 'string', 'email', 'max:255', 'unique:users'],
            self::PASSWORD => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function toDto(): RegisterUserData
    {
        return new RegisterUserData(
            familyName: $this->safe()->string(self::FAMILY_NAME)->toString(),
            name: $this->safe()->string(self::NAME)->toString(),
            email: $this->safe()->string(self::EMAIL)->toString(),
            password: $this->safe()->string(self::PASSWORD)->toString(),
        );
    }
}
