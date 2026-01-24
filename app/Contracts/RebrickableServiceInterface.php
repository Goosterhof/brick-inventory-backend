<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DataTransferObjects\SetPartsResultData;

interface RebrickableServiceInterface
{
    public function getSetParts(string $setNum): SetPartsResultData;

    /**
     * @return array{set_num: string, name: string, year: int, theme_id: int|null, num_parts: int, set_img_url: string|null}
     */
    public function fetchSet(string $setNum): array;
}
