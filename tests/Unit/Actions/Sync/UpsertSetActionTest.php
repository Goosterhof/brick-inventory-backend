<?php

declare(strict_types=1);

use App\Actions\Sync\UpsertSetAction;
use App\Models\Set;
use Illuminate\Database\Eloquent\Builder;

describe('UpsertSetAction', function (): void {
    it('should create a new set when it does not exist', function (): void {
        // arrange
        $queryBuilder = Mockery::mock(Builder::class);
        $queryBuilder->shouldReceive('where')->with('set_num', '75192-1')->once()->andReturnSelf();
        $queryBuilder->shouldReceive('first')->once()->andReturn(null);

        $newSet = Mockery::mock(Set::class)->makePartial();
        $newSet->shouldReceive('save')->once();

        $set = Mockery::mock(Set::class);
        $set->shouldReceive('newQuery')->once()->andReturn($queryBuilder);
        $set->shouldReceive('newInstance')->once()->andReturn($newSet);

        $action = new UpsertSetAction($set);

        $data = [
            'set_num' => '75192-1',
            'name' => 'Millennium Falcon',
            'year' => 2017,
            'theme_id' => 158,
            'num_parts' => 7541,
            'set_img_url' => 'https://example.com/75192.jpg',
        ];

        // act
        $result = $action->execute($data);

        // assert
        expect($result)->toBe($newSet);
        expect($result->set_num)->toBe('75192-1');
        expect($result->name)->toBe('Millennium Falcon');
        expect($result->year)->toBe(2017);
        expect($result->theme)->toBe('158');
        expect($result->num_parts)->toBe(7541);
        expect($result->image_url)->toBe('https://example.com/75192.jpg');
    });

    it('should update an existing set when it exists', function (): void {
        // arrange
        $existingSet = Mockery::mock(Set::class)->makePartial();
        $existingSet->id = 1;
        $existingSet->set_num = '75192-1';
        $existingSet->shouldReceive('save')->once();

        $queryBuilder = Mockery::mock(Builder::class);
        $queryBuilder->shouldReceive('where')->with('set_num', '75192-1')->once()->andReturnSelf();
        $queryBuilder->shouldReceive('first')->once()->andReturn($existingSet);

        $set = Mockery::mock(Set::class);
        $set->shouldReceive('newQuery')->once()->andReturn($queryBuilder);

        $action = new UpsertSetAction($set);

        $data = [
            'set_num' => '75192-1',
            'name' => 'Updated Millennium Falcon',
            'year' => 2018,
            'theme_id' => 159,
            'num_parts' => 7600,
            'set_img_url' => 'https://example.com/updated.jpg',
        ];

        // act
        $result = $action->execute($data);

        // assert
        expect($result)->toBe($existingSet);
        expect($result->name)->toBe('Updated Millennium Falcon');
        expect($result->year)->toBe(2018);
        expect($result->theme)->toBe('159');
        expect($result->num_parts)->toBe(7600);
        expect($result->image_url)->toBe('https://example.com/updated.jpg');
    });

    it('should handle null theme_id and set_img_url', function (): void {
        // arrange
        $queryBuilder = Mockery::mock(Builder::class);
        $queryBuilder->shouldReceive('where')->andReturnSelf();
        $queryBuilder->shouldReceive('first')->andReturn(null);

        $newSet = Mockery::mock(Set::class)->makePartial();
        $newSet->shouldReceive('save')->once();

        $set = Mockery::mock(Set::class);
        $set->shouldReceive('newQuery')->andReturn($queryBuilder);
        $set->shouldReceive('newInstance')->andReturn($newSet);

        $action = new UpsertSetAction($set);

        $data = [
            'set_num' => '10281-1',
            'name' => 'Bonsai Tree',
            'year' => 2021,
            'theme_id' => null,
            'num_parts' => 878,
            'set_img_url' => null,
        ];

        // act
        $result = $action->execute($data);

        // assert
        expect($result->theme)->toBeNull();
        expect($result->image_url)->toBeNull();
    });
});
