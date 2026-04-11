<?php

declare(strict_types = 1);

use App\Http\Controllers\SetController;
use App\Models\Color;
use App\Models\Part;
use App\Models\Set;
use App\Models\SetPart;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

covers(SetController::class);

uses(RefreshDatabase::class);

describe('SetController', function(): void {
    describe('parts', function(): void {
        it('should return 401 for unauthenticated requests', function(): void {
            $response = $this->getJson('/api/sets/75192-1/parts');

            $response->assertStatus(401);
        });

        it('should return parts for a cached set', function(): void {
            $user = User::factory()->create();

            $set = Set::factory()->create([
                'set_num' => '75192-1',
                'name' => 'Millennium Falcon',
                'year' => 2_017,
                'theme' => 'Star Wars',
                'num_parts' => 7_541,
                'image_url' => 'https://example.com/falcon.jpg',
            ]);

            $color = Color::factory()->create([
                'rebrickable_id' => 1,
                'name' => 'White',
                'rgb' => 'FFFFFF',
                'is_transparent' => false,
            ]);

            $part = Part::factory()->create([
                'part_num' => '3001',
                'name' => 'Brick 2 x 4',
                'category' => '11',
                'image_url' => 'https://example.com/3001.jpg',
            ]);

            $setPart = new SetPart;
            $setPart->set_id = $set->id;
            $setPart->part_id = $part->id;
            $setPart->color_id = $color->id;
            $setPart->quantity = 10;
            $setPart->is_spare = false;
            $setPart->element_id = '300101';
            $setPart->save();

            $response = $this->actingAs($user)->getJson('/api/sets/75192-1/parts');

            $response->assertStatus(200)
                ->assertJson([
                    'set_num' => '75192-1',
                    'name' => 'Millennium Falcon',
                    'year' => 2_017,
                    'num_parts' => 7_541,
                ])
                ->assertJsonCount(1, 'parts')
                ->assertJsonPath('parts.0.part.id', $part->id)
                ->assertJsonPath('parts.0.part.part_num', '3001')
                ->assertJsonPath('parts.0.color.name', 'White')
                ->assertJsonPath('parts.0.quantity', 10);
        });

        it('should fetch parts from rebrickable api when not cached', function(): void {
            $user = User::factory()->create();

            Http::fake([
                'rebrickable.com/api/v3/lego/sets/10281-1/' => Http::response([
                    'set_num' => '10281-1',
                    'name' => 'Bonsai Tree',
                    'year' => 2_021,
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

            $response = $this->actingAs($user)->getJson('/api/sets/10281-1/parts');

            $response->assertStatus(200)
                ->assertJson([
                    'set_num' => '10281-1',
                    'name' => 'Bonsai Tree',
                ])
                ->assertJsonCount(1, 'parts');

            $this->assertDatabaseHas('sets', ['set_num' => '10281-1']);
            $this->assertDatabaseHas('parts', ['part_num' => '3024']);
            $this->assertDatabaseHas('colors', ['rebrickable_id' => 6]);

            $response->assertJsonPath('parts.0.part.part_num', '3024')
                ->assertJsonPath('parts.0.color.name', 'Green');
        });

        it('should return 404 for non-existent set', function(): void {
            $user = User::factory()->create();

            Http::fake([
                'rebrickable.com/api/v3/lego/sets/99999-1/' => Http::response(
                    ['detail' => 'Not found.'],
                    404,
                ),
            ]);

            $response = $this->actingAs($user)->getJson('/api/sets/99999-1/parts');

            $response->assertStatus(404)
                ->assertJson(['error' => 'Set not found']);
        });

        it('should return 502 for invalid api key', function(): void {
            $user = User::factory()->create();

            Http::fake([
                'rebrickable.com/api/v3/lego/sets/10281-1/' => Http::response(
                    ['detail' => 'Invalid API Key.'],
                    401,
                ),
            ]);

            $response = $this->actingAs($user)->getJson('/api/sets/10281-1/parts');

            $response->assertStatus(502)
                ->assertJson(['error' => 'Invalid API key']);
        });

        it('should handle pagination from rebrickable api', function(): void {
            $user = User::factory()->create();

            Http::fake([
                'rebrickable.com/api/v3/lego/sets/42056-1/' => Http::response([
                    'set_num' => '42056-1',
                    'name' => 'Porsche 911 GT3 RS',
                    'year' => 2_016,
                    'theme_id' => 1,
                    'num_parts' => 2_704,
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

            $response = $this->actingAs($user)->getJson('/api/sets/42056-1/parts');

            $response->assertStatus(200)
                ->assertJsonCount(2, 'parts');

            $this->assertDatabaseHas('parts', ['part_num' => '32316']);
            $this->assertDatabaseHas('parts', ['part_num' => '32525']);
        });
    });

    describe('storageMap', function(): void {
        it('should return 401 for unauthenticated requests', function(): void {
            $response = $this->getJson('/api/sets/75192-1/storage-map');

            $response->assertStatus(401);
        });

        it('should return storage map for a cached set', function(): void {
            $user = User::factory()->create();

            $set = Set::factory()->create([
                'set_num' => '75192-1',
                'name' => 'Millennium Falcon',
                'year' => 2_017,
                'theme' => 'Star Wars',
                'num_parts' => 7_541,
                'image_url' => 'https://example.com/falcon.jpg',
            ]);

            $color = Color::factory()->create([
                'rebrickable_id' => 1,
                'name' => 'White',
                'rgb' => 'FFFFFF',
                'is_transparent' => false,
            ]);

            $part = Part::factory()->create([
                'part_num' => '3001',
                'name' => 'Brick 2 x 4',
                'category' => '11',
                'image_url' => 'https://example.com/3001.jpg',
            ]);

            $setPart = new SetPart;
            $setPart->set_id = $set->id;
            $setPart->part_id = $part->id;
            $setPart->color_id = $color->id;
            $setPart->quantity = 10;
            $setPart->is_spare = false;
            $setPart->element_id = '300101';
            $setPart->save();

            $response = $this->actingAs($user)->getJson('/api/sets/75192-1/storage-map');

            $response->assertStatus(200);
        });
    });

    describe('lookupByEan', function(): void {
        it('should return 401 for unauthenticated requests', function(): void {
            $response = $this->getJson('/api/sets/ean/5702016914177');

            $response->assertStatus(401);
        });

        it('should return set data when EAN matches via rebrickable api', function(): void {
            $user = User::factory()->create();

            Http::fake([
                'rebrickable.com/api/v3/lego/sets/*' => Http::response([
                    'results' => [
                        [
                            'set_num' => '75192-1',
                            'name' => 'Millennium Falcon',
                            'year' => 2_017,
                            'theme_id' => 158,
                            'num_parts' => 7_541,
                            'set_img_url' => 'https://example.com/75192.jpg',
                        ],
                    ],
                    'next' => null,
                ]),
            ]);

            $response = $this->actingAs($user)->getJson('/api/sets/ean/5702016914177');

            $response->assertStatus(200)
                ->assertJson([
                    'set_num' => '75192-1',
                    'name' => 'Millennium Falcon',
                    'year' => 2_017,
                    'num_parts' => 7_541,
                    'image_url' => 'https://example.com/75192.jpg',
                ]);

            $this->assertDatabaseHas('sets', ['set_num' => '75192-1']);
        });

        it('should return cached set when it already exists in database', function(): void {
            $user = User::factory()->create();

            $set = Set::factory()->create([
                'set_num' => '75192-1',
                'name' => 'Millennium Falcon',
                'year' => 2_017,
                'theme' => '158',
                'num_parts' => 7_541,
                'image_url' => 'https://example.com/falcon.jpg',
            ]);

            Http::fake([
                'rebrickable.com/api/v3/lego/sets/*' => Http::response([
                    'results' => [
                        [
                            'set_num' => '75192-1',
                            'name' => 'Millennium Falcon',
                            'year' => 2_017,
                            'theme_id' => 158,
                            'num_parts' => 7_541,
                            'set_img_url' => 'https://example.com/75192.jpg',
                        ],
                    ],
                    'next' => null,
                ]),
            ]);

            $response = $this->actingAs($user)->getJson('/api/sets/ean/5702016914177');

            $response->assertStatus(200)
                ->assertJson([
                    'id' => $set->id,
                    'set_num' => '75192-1',
                    'name' => 'Millennium Falcon',
                ]);
        });

        it('should return 404 when no set matches the EAN', function(): void {
            $user = User::factory()->create();

            Http::fake([
                'rebrickable.com/api/v3/lego/sets/*' => Http::response([
                    'results' => [],
                    'next' => null,
                ]),
            ]);

            $response = $this->actingAs($user)->getJson('/api/sets/ean/0000000000000');

            $response->assertStatus(404)
                ->assertJson(['error' => 'Set not found']);
        });

        it('should return 404 for invalid EAN format', function(): void {
            $user = User::factory()->create();

            $response = $this->actingAs($user)->getJson('/api/sets/ean/abc');

            $response->assertStatus(404);
        });
    });
});
