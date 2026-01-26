<?php

declare(strict_types=1);

namespace App\Data\Lego;

/**
 * DTO for LEGO set data from external APIs.
 */
final readonly class LegoSetData
{
    public function __construct(
        public string $setNum,
        public string $name,
        public int $year,
        public ?int $themeId,
        public int $numParts,
        public ?string $imageUrl,
    ) {}

    /**
     * @param array{set_num: string, name: string, year: int, theme_id: int|null, num_parts: int, set_img_url: string|null} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            setNum: $data['set_num'],
            name: $data['name'],
            year: $data['year'],
            themeId: $data['theme_id'],
            numParts: $data['num_parts'],
            imageUrl: $data['set_img_url'],
        );
    }
}
