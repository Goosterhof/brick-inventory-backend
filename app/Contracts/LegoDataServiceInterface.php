<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Set;

interface LegoDataServiceInterface
{
    public function getSetParts(string $setNum): Set;

    /**
     * @return array{set_num: string, name: string, year: int, theme_id: int|null, num_parts: int, set_img_url: string|null}
     */
    public function fetchSet(string $setNum): array;
}
