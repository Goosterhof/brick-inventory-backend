<?php

declare(strict_types=1);

namespace App\Data\Lego;

/**
 * DTO for LEGO part data from external APIs.
 */
final readonly class LegoPartData
{
    public function __construct(
        public string $partNum,
        public string $name,
        public ?int $categoryId,
        public ?string $imageUrl,
    ) {}

    /**
     * @param array{part_num: string, name: string, part_cat_id: int|null, part_img_url: string|null} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            partNum: $data['part_num'],
            name: $data['name'],
            categoryId: $data['part_cat_id'] ?? null,
            imageUrl: $data['part_img_url'] ?? null,
        );
    }
}
