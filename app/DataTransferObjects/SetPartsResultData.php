<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

final readonly class SetPartsResultData
{
    /**
     * @param  array<SetPartData>  $parts
     */
    public function __construct(
        public SetData $set,
        public array $parts,
    ) {}
}
