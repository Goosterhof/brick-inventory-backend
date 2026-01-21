<?php

declare(strict_types=1);

use App\DataTransferObjects\ColorData;
use App\DataTransferObjects\SetData;
use App\DataTransferObjects\SetPartData;
use App\DataTransferObjects\SetPartsResultData;
use App\Http\Resources\SetPartsResource;
use Illuminate\Http\Request;

describe('SetPartsResource', function (): void {
    it('should convert to array format', function (): void {
        // arrange
        $data = new SetPartsResultData(
            set: new SetData(
                setNum: '75192-1',
                name: 'Millennium Falcon',
                year: 2017,
                theme: 158,
                numParts: 7541,
                imageUrl: 'https://example.com/falcon.jpg',
            ),
            parts: [
                new SetPartData(
                    partNum: '3001',
                    name: 'Brick 2 x 4',
                    category: 'Bricks',
                    imageUrl: 'https://example.com/3001.jpg',
                    color: new ColorData(
                        id: 15,
                        name: 'White',
                        rgb: 'FFFFFF',
                        isTransparent: false,
                    ),
                    quantity: 10,
                    isSpare: false,
                    elementId: '300101',
                ),
            ],
        );

        $resource = new SetPartsResource($data);
        $request = Request::create('/api/sets/75192-1/parts');

        // act
        $array = $resource->toArray($request);

        // assert
        expect($array)->toBeArray()
            ->and($array['set'])->toBe([
                'set_num' => '75192-1',
                'name' => 'Millennium Falcon',
                'year' => 2017,
                'theme' => 158,
                'num_parts' => 7541,
                'image_url' => 'https://example.com/falcon.jpg',
            ])
            ->and($array['parts'])->toHaveCount(1)
            ->and($array['parts'][0])->toBe([
                'part_num' => '3001',
                'name' => 'Brick 2 x 4',
                'category' => 'Bricks',
                'image_url' => 'https://example.com/3001.jpg',
                'color' => [
                    'id' => 15,
                    'name' => 'White',
                    'rgb' => 'FFFFFF',
                    'is_transparent' => false,
                ],
                'quantity' => 10,
                'is_spare' => false,
                'element_id' => '300101',
            ]);
    });

    it('should handle multiple parts', function (): void {
        // arrange
        $data = new SetPartsResultData(
            set: new SetData(
                setNum: '10179-1',
                name: 'Ultimate Collector Millennium Falcon',
                year: 2007,
                theme: 158,
                numParts: 5195,
                imageUrl: null,
            ),
            parts: [
                new SetPartData(
                    partNum: '3001',
                    name: 'Brick 2 x 4',
                    category: 'Bricks',
                    imageUrl: null,
                    color: new ColorData(id: 15, name: 'White', rgb: 'FFFFFF', isTransparent: false),
                    quantity: 5,
                    isSpare: false,
                    elementId: null,
                ),
                new SetPartData(
                    partNum: '3002',
                    name: 'Brick 2 x 3',
                    category: 'Bricks',
                    imageUrl: null,
                    color: new ColorData(id: 0, name: 'Black', rgb: '000000', isTransparent: false),
                    quantity: 3,
                    isSpare: true,
                    elementId: '300226',
                ),
            ],
        );

        $resource = new SetPartsResource($data);
        $request = Request::create('/api/sets/10179-1/parts');

        // act
        $array = $resource->toArray($request);

        // assert
        expect($array['parts'])->toHaveCount(2)
            ->and($array['parts'][0]['part_num'])->toBe('3001')
            ->and($array['parts'][1]['part_num'])->toBe('3002')
            ->and($array['parts'][1]['is_spare'])->toBeTrue();
    });
});
