<?php

use Ozankurt\Select3\Builder;
use Ozankurt\Select3\Select3;
use Ozankurt\Select3\Timezones;

it('lists every IANA zone grouped by region with an offset', function () {
    $rows = Timezones::options();

    expect($rows)->toHaveCount(count(DateTimeZone::listIdentifiers()));

    $london = collect($rows)->firstWhere('value', 'Europe/London');
    expect($london)
        ->label->toBe('London')
        ->group->toBe('Europe')
        ->meta->toMatch('/^[+-]\d{2}:\d{2}$/');
});

it('computes offsets at the given moment, honouring daylight saving', function () {
    $summer = collect(Timezones::options(['Europe'], new DateTimeImmutable('2026-07-01 12:00 UTC')));
    $winter = collect(Timezones::options(['Europe'], new DateTimeImmutable('2026-01-01 12:00 UTC')));

    expect($summer->firstWhere('value', 'Europe/London')['meta'])->toBe('+01:00')
        ->and($winter->firstWhere('value', 'Europe/London')['meta'])->toBe('+00:00')
        ->and($summer->firstWhere('value', 'Europe/Istanbul')['meta'])->toBe('+03:00');
});

it('filters to the requested regions and sorts by region then city', function () {
    $rows = Timezones::options(['Europe', 'Africa']);
    $groups = array_values(array_unique(array_column($rows, 'group')));

    expect($groups)->toBe(['Africa', 'Europe']);

    $africa = array_column(array_filter($rows, fn ($r) => $r['group'] === 'Africa'), 'label');
    $sorted = $africa;
    sort($sorted);
    expect($africa)->toBe(array_values($sorted));
});

it('labels nested zones as "City, Subregion" and keeps UTC on its own', function () {
    $rows = collect(Timezones::options(['America', 'UTC']));

    expect($rows->firstWhere('value', 'America/Argentina/Buenos_Aires')['label'])->toBe('Buenos Aires, Argentina')
        ->and($rows->firstWhere('value', 'UTC'))->toMatchArray(['label' => 'UTC', 'group' => 'UTC']);
});

it('adds search keywords for the identifier, UTC offset and abbreviation', function () {
    $london = collect(Timezones::options(['Europe'], new DateTimeImmutable('2026-07-01 12:00 UTC')))
        ->firstWhere('value', 'Europe/London');

    expect($london['data']['keywords'])
        ->toContain('Europe/London')
        ->toContain('UTC+01:00')
        ->toContain('BST');
});

it('renders a region-grouped timezone select with search over meta and keywords', function () {
    $builder = Builder::make('timezone')->timezones(['Europe']);
    $html = $builder->toHtml();

    expect($html)
        ->toContain('<optgroup label="Europe">')
        ->toMatch('/<option value="Europe\/London" data-meta="[+-]\d{2}:\d{2}" data-keywords="[^"]*Europe\/London[^"]*">London<\/option>/')
        ->and($builder->toConfig())
        ->toMatchArray(['searchField' => ['label', 'meta', 'keywords'], 'searchGroups' => true]);
});

it('keeps a caller-chosen searchField', function () {
    $config = Builder::make('tz')->searchField(['label'])->timezones(['Europe'])->toConfig();

    expect($config['searchField'])->toBe(['label']);
});

it('exposes Select3::timezones() with a pinned default listed first', function () {
    $html = Select3::timezones()
        ->pin('inherit', 'Same as Acme (Europe/London)')
        ->toHtml();

    expect($html)
        ->toContain('name="timezone"')
        ->toMatch('/<select[^>]*><option value="inherit" data-pinned>Same as Acme \(Europe\/London\)<\/option><optgroup label="Africa">/');
});

it('pins existing values through config', function () {
    $config = Builder::make('tz')->pinned('Europe/London')->pinned(['UTC', 'Europe/London'])->toConfig();

    expect($config['pinned'])->toBe(['Europe/London', 'UTC']);
});

it('emits extra data attributes and drops unsafe attribute names', function () {
    $html = Builder::make('x')
        ->option('a', 'A', ['meta' => '+03:00', 'data' => ['keywords' => 'uk "gb"', 'bad name' => 'x', 'onclick' => '']])
        ->toHtml();

    expect($html)
        ->toContain('data-meta="+03:00"')
        ->toContain('data-keywords="uk &quot;gb&quot;"')
        ->not->toContain('bad name');
});
