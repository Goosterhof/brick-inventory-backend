<?php

declare(strict_types=1);

namespace App\Http\Requests\Family;

use App\Contracts\Family\SetRebrickableTokenInterface;
use App\Http\Requests\DTOFormRequest;
use Illuminate\Http\Request;

final readonly class SetRebrickableTokenRequest extends DTOFormRequest implements SetRebrickableTokenInterface
{
    public const string REBRICKABLE_USER_TOKEN = 'rebrickable_user_token';

    public function __construct(
        public string $rebrickableUserToken,
    ) {}

    public static function rules(Request $request): array
    {
        return [
            self::REBRICKABLE_USER_TOKEN => ['required', 'string', 'max:255'],
        ];
    }

    protected static function toDTO(Request $request): static
    {
        return new self(
            rebrickableUserToken: $request->string(self::REBRICKABLE_USER_TOKEN)->toString(),
        );
    }
}
