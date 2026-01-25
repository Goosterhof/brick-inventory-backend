<?php

declare(strict_types=1);

namespace App\Data\Lego;

/**
 * DTO for LEGO color data from external APIs.
 */
final readonly class LegoColorData
{
    public function __construct(
        public int $id,
        public string $name,
        public string $rgb,
        public bool $isTransparent,
    ) {}

    /**
     * @param  array{id: int, name: string, rgb: string, is_trans: bool}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            rgb: $data['rgb'],
            isTransparent: $data['is_trans'],
        );
    }
}
