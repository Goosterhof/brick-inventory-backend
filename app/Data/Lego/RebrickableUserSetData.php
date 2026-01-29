<?php

declare(strict_types=1);

namespace App\Data\Lego;

/**
 * DTO for a set in a user's Rebrickable collection.
 */
final readonly class RebrickableUserSetData
{
    public function __construct(
        public LegoSetData $set,
        public int $quantity,
    ) {}

    /**
     * @param array{set: array{set_num: string, name: string, year: int, theme_id: int|null, num_parts: int, set_img_url: string|null}, quantity: int} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            set: LegoSetData::fromArray($data['set']),
            quantity: $data['quantity'],
        );
    }
}
