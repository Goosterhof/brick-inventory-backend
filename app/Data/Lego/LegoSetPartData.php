<?php

declare(strict_types=1);

namespace App\Data\Lego;

/**
 * DTO for a part within a LEGO set from external APIs.
 */
final readonly class LegoSetPartData
{
    public function __construct(
        public LegoPartData $part,
        public LegoColorData $color,
        public int $quantity,
        public bool $isSpare,
        public ?string $elementId,
    ) {}

    /**
     * @param  array{part: array{part_num: string, name: string, part_cat_id: int|null, part_img_url: string|null}, color: array{id: int, name: string, rgb: string, is_trans: bool}, quantity: int, is_spare: bool, element_id: string|null}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            part: LegoPartData::fromArray($data['part']),
            color: LegoColorData::fromArray($data['color']),
            quantity: $data['quantity'],
            isSpare: $data['is_spare'],
            elementId: $data['element_id'] ?? null,
        );
    }
}
