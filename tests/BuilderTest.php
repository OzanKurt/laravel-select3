<?php

use Ozankurt\Select3\Builder;
use Ozankurt\Select3\Select3;

it('renders a native select with data-select3 and options', function () {
    $html = Builder::make('country')
        ->placeholder('Pick')
        ->options(['au' => 'Australia', 'nz' => 'New Zealand'])
        ->toHtml();

    expect($html)
        ->toContain('<select')
        ->toContain('name="country"')
        ->toContain('data-select3')
        ->toContain('data-placeholder="Pick"')
        ->toContain('<option value="au">Australia</option>')
        ->toContain('<option value="nz">New Zealand</option>');
});

it('exposes Select3::make() as the builder entry point', function () {
    expect(Select3::make('x'))->toBeInstanceOf(Builder::class);
});

it('adds [] to the name and a multiple attribute for multi-selects', function () {
    $html = Builder::make('tags')->multiple()->toHtml();
    expect($html)->toContain('name="tags[]"')->toContain(' multiple');
});

it('emits rich per-option data attributes', function () {
    $html = Builder::make('users')
        ->option('1', 'Sara', ['icon' => 'fa fa-user', 'badge' => 'Pro', 'description' => 'Admin'])
        ->toHtml();

    expect($html)
        ->toContain('data-icon="fa fa-user"')
        ->toContain('data-badge="Pro"')
        ->toContain('data-description="Admin"');
});

it('renders optgroups when options carry a group', function () {
    $html = Builder::make('c')->options([
        ['value' => 'au', 'label' => 'Australia', 'group' => 'Oceania'],
        ['value' => 'jp', 'label' => 'Japan', 'group' => 'Asia'],
    ])->toHtml();

    expect($html)
        ->toContain('<optgroup label="Oceania">')
        ->toContain('<optgroup label="Asia">')
        ->toContain('</optgroup>');
});

it('marks selected options', function () {
    $html = Builder::make('c')->options(['a' => 'A', 'b' => 'B'])->selected('b')->toHtml();
    expect($html)->toContain('<option value="b" selected>B</option>');
});

it('escapes option labels and values', function () {
    $html = Builder::make('c')->option('x"&', '<b>hi</b>')->toHtml();
    expect($html)->toContain('&lt;b&gt;hi&lt;/b&gt;')->not->toContain('<b>hi</b>');
});

it('serializes select3 options into data-s3-config json', function () {
    $html = Builder::make('c')->placeholder('Pick')->tags()->maxItems(3)->theme('bootstrap5')->toHtml();

    expect($html)->toContain('data-s3-config=');

    preg_match('/data-s3-config="([^"]*)"/', $html, $m);
    $cfg = json_decode(html_entity_decode($m[1], ENT_QUOTES), true);

    expect($cfg)->toMatchArray([
        'placeholder' => 'Pick',
        'tags' => true,
        'maxItems' => 3,
        'theme' => 'bootstrap5',
    ]);
});
