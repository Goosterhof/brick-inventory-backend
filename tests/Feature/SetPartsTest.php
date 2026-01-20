<?php

declare(strict_types=1);

use App\Models\Color;
use App\Models\Part;
use App\Models\Set;
use App\Models\SetPart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('it returns parts for a cached set', function (): void {
    $set = Set::create([
        'set_num' => '75192-1',
        'name' => 'Millennium Falcon',
        'year' => 2017,
        'theme' => 'Star Wars',
        'num_parts' => 7541,
        'image_url' => 'https://example.com/falcon.jpg',
    ]);

    $color = Color::create([
        'rebrickable_id' => 1,
        'name' => 'White',
        'rgb' => 'FFFFFF',
        'is_transparent' => false,
    ]);

    $part = Part::create([
        'part_num' => '3001',
        'name' => 'Brick 2 x 4',
        'category' => '11',
        'image_url' => 'https://example.com/3001.jpg',
    ]);

    SetPart::create([
        'set_id' => $set->id,
        'part_id' => $part->id,
        'color_id' => $color->id,
        'quantity' => 10,
        'is_spare' => false,
        'element_id' => '300101',
    ]);

    $response = $this->getJson('/api/sets/75192-1/parts');

    $response->assertStatus(200)
        ->assertJson([
            'set' => [
                'set_num' => '75192-1',
                'name' => 'Millennium Falcon',
                'year' => 2017,
                'num_parts' => 7541,
            ],
        ])
        ->assertJsonCount(1, 'parts')
        ->assertJsonPath('parts.0.part_num', '3001')
        ->assertJsonPath('parts.0.quantity', 10);
});

test('it fetches parts from rebrickable api when not cached', function (): void {
    Http::fake([
        'rebrickable.com/api/v3/lego/sets/10281-1/' => Http::response([
            'set_num' => '10281-1',
            'name' => 'Bonsai Tree',
            'year' => 2021,
            'theme_id' => 598,
            'num_parts' => 878,
            'set_img_url' => 'https://example.com/bonsai.jpg',
        ]),
        'rebrickable.com/api/v3/lego/sets/10281-1/parts/*' => Http::response([
            'count' => 1,
            'next' => null,
            'results' => [
                [
                    'part' => [
                        'part_num' => '3024',
                        'name' => 'Plate 1 x 1',
                        'part_cat_id' => 14,
                        'part_img_url' => 'https://example.com/3024.jpg',
                    ],
                    'color' => [
                        'id' => 6,
                        'name' => 'Green',
                        'rgb' => '237841',
                        'is_trans' => false,
                    ],
                    'quantity' => 15,
                    'is_spare' => false,
                    'element_id' => '302428',
                ],
            ],
        ]),
    ]);

    $response = $this->getJson('/api/sets/10281-1/parts');

    $response->assertStatus(200)
        ->assertJson([
            'set' => [
                'set_num' => '10281-1',
                'name' => 'Bonsai Tree',
            ],
        ])
        ->assertJsonCount(1, 'parts')
        ->assertJsonPath('parts.0.part_num', '3024')
        ->assertJsonPath('parts.0.color.name', 'Green');

    $this->assertDatabaseHas('sets', ['set_num' => '10281-1']);
    $this->assertDatabaseHas('parts', ['part_num' => '3024']);
    $this->assertDatabaseHas('colors', ['rebrickable_id' => 6]);
});

test('it returns 404 for non-existent set', function (): void {
    Http::fake([
        'rebrickable.com/api/v3/lego/sets/99999-1/' => Http::response(
            ['detail' => 'Not found.'],
            404,
        ),
    ]);

    $response = $this->getJson('/api/sets/99999-1/parts');

    $response->assertStatus(404)
        ->assertJson(['error' => 'Set not found']);
});

test('it returns 401 for invalid api key', function (): void {
    Http::fake([
        'rebrickable.com/api/v3/lego/sets/10281-1/' => Http::response(
            ['detail' => 'Invalid API Key.'],
            401,
        ),
    ]);

    $response = $this->getJson('/api/sets/10281-1/parts');

    $response->assertStatus(401)
        ->assertJson(['error' => 'Invalid API key']);
});

test('it handles pagination from rebrickable api', function (): void {
    Http::fake([
        'rebrickable.com/api/v3/lego/sets/42056-1/' => Http::response([
            'set_num' => '42056-1',
            'name' => 'Porsche 911 GT3 RS',
            'year' => 2016,
            'theme_id' => 1,
            'num_parts' => 2704,
            'set_img_url' => 'https://example.com/porsche.jpg',
        ]),
        'rebrickable.com/api/v3/lego/sets/42056-1/parts/' => Http::response([
            'count' => 2,
            'next' => 'https://rebrickable.com/api/v3/lego/sets/42056-1/parts/?page=2',
            'results' => [
                [
                    'part' => [
                        'part_num' => '32316',
                        'name' => 'Technic, Liftarm 1 x 5 Thick',
                        'part_cat_id' => 56,
                        'part_img_url' => 'https://example.com/32316.jpg',
                    ],
                    'color' => [
                        'id' => 46,
                        'name' => 'Trans-Yellow',
                        'rgb' => 'F5CD2F',
                        'is_trans' => true,
                    ],
                    'quantity' => 4,
                    'is_spare' => false,
                    'element_id' => null,
                ],
            ],
        ]),
        'rebrickable.com/api/v3/lego/sets/42056-1/parts/?page=2' => Http::response([
            'count' => 2,
            'next' => null,
            'results' => [
                [
                    'part' => [
                        'part_num' => '32525',
                        'name' => 'Technic, Liftarm 1 x 11 Thick',
                        'part_cat_id' => 56,
                        'part_img_url' => 'https://example.com/32525.jpg',
                    ],
                    'color' => [
                        'id' => 0,
                        'name' => 'Black',
                        'rgb' => '05131D',
                        'is_trans' => false,
                    ],
                    'quantity' => 2,
                    'is_spare' => false,
                    'element_id' => '4142822',
                ],
            ],
        ]),
    ]);

    $response = $this->getJson('/api/sets/42056-1/parts');

    $response->assertStatus(200)
        ->assertJsonCount(2, 'parts');

    $this->assertDatabaseHas('parts', ['part_num' => '32316']);
    $this->assertDatabaseHas('parts', ['part_num' => '32525']);
});
