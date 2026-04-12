<?php

declare(strict_types = 1);

namespace App\Actions\FamilySet;

use App\Data\FamilySetCompletionData;
use App\Enums\FamilySetStatus;
use App\Models\Family;
use App\Models\FamilySet;
use App\Models\SetPart;
use App\Models\StorageOption;
use App\Models\StorageOptionPart;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use stdClass;

final readonly class GetFamilySetCompletionAction
{
    public function __construct(
        private FamilySet $familySet,
        private SetPart $setPart,
        private StorageOption $storageOption,
        private StorageOptionPart $storageOptionPart,
    ) {}

    /**
     * @return list<FamilySetCompletionData>
     */
    public function execute(Family $family): array
    {
        $familySets = $this->familySet->newQuery()
            ->where('family_id', $family->id)
            ->where('status', '!=', FamilySetStatus::Wishlist)
            ->with('set')
            ->get();

        if ($familySets->isEmpty()) {
            return [];
        }

        $setIds = $familySets->pluck('set_id')->unique()->values();

        // Count total unique part+color combinations per set (non-spare parts only)
        /** @var Collection<int, stdClass> $totalPartsCounts */
        $totalPartsCounts = $this->setPart->newQuery()
            ->whereIn('set_id', $setIds)
            ->where('is_spare', false)
            ->selectRaw('set_id, COUNT(*) as total_parts')
            ->groupBy('set_id')
            ->toBase()
            ->get()
            ->keyBy('set_id');

        // Get family's storage option IDs
        $storageOptionIds = $this->storageOption->newQuery()
            ->where('family_id', $family->id)
            ->pluck('id');

        // Count stored unique part+color combinations per set
        // Uses COUNT(DISTINCT part_id || '-' || color_id) for SQLite compatibility
        /** @var Collection<int, stdClass> $storedPartsCounts */
        $storedPartsCounts = collect();

        if ($storageOptionIds->isNotEmpty()) {
            /** @var Collection<int, stdClass> $storedPartsCounts */
            $storedPartsCounts = $this->storageOptionPart->newQuery()
                ->whereIn('storage_option_parts.storage_option_id', $storageOptionIds)
                ->where('storage_option_parts.quantity', '>', 0)
                ->join('set_parts', function(JoinClause $joinClause) use ($setIds): void {
                    $joinClause->on('storage_option_parts.part_id', '=', 'set_parts.part_id')
                        ->on('storage_option_parts.color_id', '=', 'set_parts.color_id')
                        ->whereIn('set_parts.set_id', $setIds)
                        ->where('set_parts.is_spare', false);
                })
                ->selectRaw("set_parts.set_id, COUNT(DISTINCT CAST(set_parts.part_id AS TEXT) || '-' || CAST(set_parts.color_id AS TEXT)) as stored_parts")
                ->groupBy('set_parts.set_id')
                ->toBase()
                ->get()
                ->keyBy('set_id');
        }

        /** @var list<FamilySetCompletionData> */
        return array_values($familySets->map(function(FamilySet $familySet) use ($totalPartsCounts, $storedPartsCounts): FamilySetCompletionData {
            $setId = $familySet->set_id;
            $totalPartsRow = $totalPartsCounts->get($setId);

            // No set_parts rows means parts were never fetched from Rebrickable
            if ($totalPartsRow === null) {
                return new FamilySetCompletionData(
                    familySetId: $familySet->id,
                    setNum: $familySet->set->set_num,
                    totalParts: null,
                    storedParts: null,
                    percentage: null,
                );
            }

            $totalParts = (int) $totalPartsRow->total_parts; // @phpstan-ignore cast.int
            $storedPartsRow = $storedPartsCounts->get($setId);
            $storedParts = $storedPartsRow !== null ? (int) $storedPartsRow->stored_parts : 0; // @phpstan-ignore cast.int

            $percentage = $totalParts > 0
                ? min(round($storedParts / $totalParts * 100, 2), 100.0)
                : 0.0;

            return new FamilySetCompletionData(
                familySetId: $familySet->id,
                setNum: $familySet->set->set_num,
                totalParts: $totalParts,
                storedParts: $storedParts,
                percentage: $percentage,
            );
        })->all());
    }
}
