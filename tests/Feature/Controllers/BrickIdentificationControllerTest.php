<?php

declare(strict_types=1);

use App\Models\Part;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

describe('BrickIdentificationController', function (): void {
    describe('identify', function (): void {
        it('should identify a brick and return the part', function (): void {
            // arrange
            $user = User::factory()->create();
            $part = Part::factory()->create([
                'part_num' => '3001',
                'name' => 'Brick 2 x 4',
            ]);

            Http::fake([
                'https://api.brickognize.com/predict/' => Http::response([
                    'items' => [
                        [
                            'id' => '3001',
                            'name' => 'Brick 2 x 4',
                            'type' => 'part',
                            'img_url' => 'https://example.com/3001.jpg',
                            'score' => 0.95,
                        ],
                    ],
                ]),
            ]);

            $image = UploadedFile::fake()->image('brick.jpg');

            // act
            $response = $this->actingAs($user)->postJson('/api/identify-brick', [
                'image' => $image,
            ]);

            // assert
            $response->assertStatus(200)
                ->assertJsonPath('id', $part->id)
                ->assertJsonPath('part_num', '3001')
                ->assertJsonPath('name', 'Brick 2 x 4');
        });

        it('should return 401 when unauthenticated', function (): void {
            // arrange
            $image = UploadedFile::fake()->image('brick.jpg');

            // act
            $response = $this->postJson('/api/identify-brick', [
                'image' => $image,
            ]);

            // assert
            $response->assertStatus(401);
        });

        it('should return 422 when no image provided', function (): void {
            // arrange
            $user = User::factory()->create();

            // act
            $response = $this->actingAs($user)->postJson('/api/identify-brick', []);

            // assert
            $response->assertStatus(422)
                ->assertJsonValidationErrors(['image']);
        });

        it('should return 422 when file is not an image', function (): void {
            // arrange
            $user = User::factory()->create();
            $file = UploadedFile::fake()->create('document.pdf', 100);

            // act
            $response = $this->actingAs($user)->postJson('/api/identify-brick', [
                'image' => $file,
            ]);

            // assert
            $response->assertStatus(422)
                ->assertJsonValidationErrors(['image']);
        });

        it('should return 404 when identified part not in database', function (): void {
            // arrange
            $user = User::factory()->create();

            Http::fake([
                'https://api.brickognize.com/predict/' => Http::response([
                    'items' => [
                        [
                            'id' => '99999',
                            'name' => 'Unknown Part',
                            'type' => 'part',
                            'img_url' => null,
                            'score' => 0.95,
                        ],
                    ],
                ]),
            ]);

            $image = UploadedFile::fake()->image('brick.jpg');

            // act
            $response = $this->actingAs($user)->postJson('/api/identify-brick', [
                'image' => $image,
            ]);

            // assert
            $response->assertStatus(404)
                ->assertJsonPath('error', 'Part not found');
        });

        it('should return 500 when Brickognize API fails', function (): void {
            // arrange
            $user = User::factory()->create();

            Http::fake([
                'https://api.brickognize.com/predict/' => Http::response([], 500),
            ]);

            $image = UploadedFile::fake()->image('brick.jpg');

            // act
            $response = $this->actingAs($user)->postJson('/api/identify-brick', [
                'image' => $image,
            ]);

            // assert
            $response->assertStatus(500)
                ->assertJsonPath('error', 'Failed to identify brick');
        });

        it('should return 500 when no parts identified in image', function (): void {
            // arrange
            $user = User::factory()->create();

            Http::fake([
                'https://api.brickognize.com/predict/' => Http::response([
                    'items' => [],
                ]),
            ]);

            $image = UploadedFile::fake()->image('brick.jpg');

            // act
            $response = $this->actingAs($user)->postJson('/api/identify-brick', [
                'image' => $image,
            ]);

            // assert
            $response->assertStatus(500)
                ->assertJsonPath('error', 'Failed to identify brick');
        });

        it('should select best matching part from multiple predictions', function (): void {
            // arrange
            $user = User::factory()->create();
            Part::factory()->create([
                'part_num' => '3002',
                'name' => 'Brick 2 x 3',
            ]);
            $part = Part::factory()->create([
                'part_num' => '3001',
                'name' => 'Brick 2 x 4',
            ]);

            Http::fake([
                'https://api.brickognize.com/predict/' => Http::response([
                    'items' => [
                        [
                            'id' => '3002',
                            'name' => 'Brick 2 x 3',
                            'type' => 'part',
                            'img_url' => null,
                            'score' => 0.72,
                        ],
                        [
                            'id' => '3001',
                            'name' => 'Brick 2 x 4',
                            'type' => 'part',
                            'img_url' => null,
                            'score' => 0.95, // Higher score
                        ],
                    ],
                ]),
            ]);

            $image = UploadedFile::fake()->image('brick.jpg');

            // act
            $response = $this->actingAs($user)->postJson('/api/identify-brick', [
                'image' => $image,
            ]);

            // assert
            $response->assertStatus(200)
                ->assertJsonPath('id', $part->id)
                ->assertJsonPath('part_num', '3001');
        });
    });
});
