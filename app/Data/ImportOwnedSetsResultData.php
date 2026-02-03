<?php

declare(strict_types=1);

namespace App\Data;

use JsonSerializable;

/**
 * DTO for the result of importing owned sets from Rebrickable.
 *
 * Skipped sets are those where multiple FamilySet rows exist for the same set,
 * requiring manual reconciliation.
 */
final readonly class ImportOwnedSetsResultData implements JsonSerializable
{
    /**
     * @param array<string> $skippedSetNums Set numbers that were skipped due to duplicates
     */
    public function __construct(
        public int $created,
        public int $updated,
        public int $skipped,
        public int $total,
        public array $skippedSetNums = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $data = [
            'message' => 'Import completed successfully',
            'created' => $this->created,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
            'total' => $this->total,
        ];

        if ($this->skippedSetNums !== []) {
            $data['skipped_set_nums'] = $this->skippedSetNums;
        }

        return $data;
    }
}
