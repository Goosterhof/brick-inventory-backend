<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Set;
use Illuminate\Database\Seeder;

class SetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $records = [
            // TODO: Replace with real LEGO set data from Rebrickable
            ['set_num' => '10297-1', 'name' => 'Boutique Hotel', 'year' => 2022, 'theme' => 'Creator Expert', 'num_parts' => 3066, 'image_url' => null],
            ['set_num' => '10305-1', 'name' => "Lion Knights' Castle", 'year' => 2022, 'theme' => 'Icons', 'num_parts' => 4514, 'image_url' => null],
            ['set_num' => '10312-1', 'name' => 'Jazz Club', 'year' => 2023, 'theme' => 'Icons', 'num_parts' => 2899, 'image_url' => null],
            ['set_num' => '75192-1', 'name' => 'Millennium Falcon', 'year' => 2017, 'theme' => 'Star Wars', 'num_parts' => 7541, 'image_url' => null],
            ['set_num' => '42151-1', 'name' => 'Bugatti Bolide', 'year' => 2023, 'theme' => 'Technic', 'num_parts' => 905, 'image_url' => null],
            ['set_num' => '21054-1', 'name' => 'The White House', 'year' => 2020, 'theme' => 'Architecture', 'num_parts' => 1483, 'image_url' => null],
            ['set_num' => '71411-1', 'name' => 'The Mighty Bowser', 'year' => 2022, 'theme' => 'Super Mario', 'num_parts' => 2807, 'image_url' => null],
            ['set_num' => '10294-1', 'name' => 'Titanic', 'year' => 2021, 'theme' => 'Creator Expert', 'num_parts' => 9090, 'image_url' => null],
            ['set_num' => '10311-1', 'name' => 'Orchid', 'year' => 2022, 'theme' => 'Botanical Collection', 'num_parts' => 608, 'image_url' => null],
            ['set_num' => '21330-1', 'name' => 'Home Alone', 'year' => 2021, 'theme' => 'Ideas', 'num_parts' => 3957, 'image_url' => null],
        ];

        foreach ($records as $record) {
            $set = new Set;
            $set->set_num = $record['set_num'];
            $set->name = $record['name'];
            $set->year = $record['year'];
            $set->theme = $record['theme'];
            $set->num_parts = $record['num_parts'];
            $set->image_url = $record['image_url'];
            $set->save();
        }
    }
}
