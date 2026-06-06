<?php

use Ozankurt\Select3\Search\Select3Search;

it('shapes rows into value/label and flags hasMore', function () {
    $out = Select3Search::make(null)
        ->label('name')->value('id')->perPage(2)
        ->shape(collect([
            ['id' => 1, 'name' => 'A'],
            ['id' => 2, 'name' => 'B'],
            ['id' => 3, 'name' => 'C'], // the perPage + 1 sentinel
        ]));

    expect($out['hasMore'])->toBeTrue();
    expect($out['results'])->toHaveCount(2);
    expect($out['results'][0])->toEqual(['value' => '1', 'label' => 'A']);
});

it('reports no more pages when the result set fits', function () {
    $out = Select3Search::make(null)
        ->perPage(5)->label('name')->value('id')
        ->shape(collect([['id' => 1, 'name' => 'A']]));

    expect($out['hasMore'])->toBeFalse();
    expect($out['results'])->toHaveCount(1);
});

it('merges extra per-row data', function () {
    $out = Select3Search::make(null)
        ->label('name')->value('id')
        ->extra(fn ($m) => ['icon' => 'fa fa-user', 'description' => $m['role']])
        ->perPage(10)
        ->shape(collect([['id' => 7, 'name' => 'Sara', 'role' => 'Admin']]));

    expect($out['results'][0])->toEqual([
        'value' => '7',
        'label' => 'Sara',
        'icon' => 'fa fa-user',
        'description' => 'Admin',
    ]);
});

it('accepts a closure label', function () {
    $out = Select3Search::make(null)
        ->label(fn ($m) => strtoupper($m['name']))->value('id')->perPage(10)
        ->shape(collect([['id' => 1, 'name' => 'sara']]));

    expect($out['results'][0]['label'])->toBe('SARA');
});

it('adds a group field from a column when group() is set', function () {
    $out = Select3Search::make(null)
        ->label('name')->value('id')->group('category')->perPage(10)
        ->shape(collect([['id' => 1, 'name' => 'Apple', 'category' => 'Fruit']]));

    expect($out['results'][0])->toEqual(['value' => '1', 'label' => 'Apple', 'group' => 'Fruit']);
});

it('supports a closure group', function () {
    $out = Select3Search::make(null)
        ->label('name')->value('id')->group(fn ($m) => strtoupper($m['category']))->perPage(10)
        ->shape(collect([['id' => 1, 'name' => 'Apple', 'category' => 'fruit']]));

    expect($out['results'][0]['group'])->toBe('FRUIT');
});
