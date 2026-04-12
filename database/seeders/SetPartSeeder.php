<?php

declare(strict_types = 1);

namespace Database\Seeders;

use App\Models\SetPart;
use Illuminate\Database\Seeder;

class SetPartSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $records = [
            // TODO: Replace with real LEGO set part data
            // Note: set_id, part_id, and color_id must reference existing records
            ['set_id' => 1, 'part_id' => 1, 'color_id' => 1, 'quantity' => 10, 'is_spare' => false, 'element_id' => null],
            ['set_id' => 1, 'part_id' => 2, 'color_id' => 1, 'quantity' => 8, 'is_spare' => false, 'element_id' => null],
            ['set_id' => 1, 'part_id' => 3, 'color_id' => 2, 'quantity' => 15, 'is_spare' => false, 'element_id' => null],
            ['set_id' => 1, 'part_id' => 4, 'color_id' => 3, 'quantity' => 20, 'is_spare' => false, 'element_id' => null],
            ['set_id' => 1, 'part_id' => 5, 'color_id' => 4, 'quantity' => 5, 'is_spare' => true, 'element_id' => null],
            ['set_id' => 2, 'part_id' => 1, 'color_id' => 5, 'quantity' => 12, 'is_spare' => false, 'element_id' => null],
            ['set_id' => 2, 'part_id' => 6, 'color_id' => 6, 'quantity' => 25, 'is_spare' => false, 'element_id' => null],
            ['set_id' => 2, 'part_id' => 7, 'color_id' => 7, 'quantity' => 18, 'is_spare' => false, 'element_id' => null],
            ['set_id' => 3, 'part_id' => 8, 'color_id' => 8, 'quantity' => 30, 'is_spare' => false, 'element_id' => null],
            ['set_id' => 3, 'part_id' => 9, 'color_id' => 9, 'quantity' => 22, 'is_spare' => false, 'element_id' => null],
        ];

        foreach ($records as $record) {
            $setPart = new SetPart;
            $setPart->set_id = $record['set_id'];
            $setPart->part_id = $record['part_id'];
            $setPart->color_id = $record['color_id'];
            $setPart->quantity = $record['quantity'];
            $setPart->is_spare = $record['is_spare'];
            $setPart->element_id = $record['element_id'];
            $setPart->save();
        }
    }
}
