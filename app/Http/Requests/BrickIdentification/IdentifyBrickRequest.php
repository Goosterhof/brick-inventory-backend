<?php

declare(strict_types=1);

namespace App\Http\Requests\BrickIdentification;

use App\Contracts\BrickIdentification\IdentifyBrickInterface;
use App\Http\Requests\DTOFormRequest;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

final readonly class IdentifyBrickRequest extends DTOFormRequest implements IdentifyBrickInterface
{
    public const string IMAGE = 'image';

    public function __construct(
        public UploadedFile $image,
    ) {}

    public static function rules(Request $request): array
    {
        return [
            self::IMAGE => ['required', 'image', 'max:10240'], // Max 10MB
        ];
    }

    protected static function toDTO(Request $request): static
    {
        $image = $request->file(self::IMAGE);
        assert($image instanceof UploadedFile);

        return new self(image: $image);
    }
}
