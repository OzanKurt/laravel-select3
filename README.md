# ozankurt/laravel-select3

Laravel companion for [`@ozankurt/select3`](https://github.com/OzanKurt/select3). It
generates the markup, config, and AJAX responses the JS enhancer reads, so you
build rich, server-driven dropdowns in PHP: a fluent builder, a Blade component,
and an AJAX search endpoint with per-row icons/images/badges.

The JS package stays the source of truth; this package only emits what it reads.

## Install

```bash
composer require ozankurt/laravel-select3
php artisan vendor:publish --tag=select3-config   # optional defaults
```

Make sure the JS side is installed and enhanced (see `@ozankurt/select3`).

## Fluent builder

```php
use Ozankurt\Select3\Select3;

echo Select3::make('country')
    ->placeholder('Pick a country')
    ->searchable()
    ->options(['au' => 'Australia', 'nz' => 'New Zealand']);
```

From a model, with rich per-row data and grouping:

```php
echo Select3::make('user_id')
    ->fromModel(\App\Models\User::query()->orderBy('name'), label: 'name', value: 'id',
        extra: fn ($u) => ['image' => $u->avatar_url, 'description' => $u->email])
    ->placeholder('Assign a user')
    ->multiple()
    ->maxItems(3);
```

Renders a native `<select data-select3 data-s3-config="{…}">` with `<option>`s
carrying `data-icon` / `data-image` / `data-description` / `data-badge`.

Tune search + pagination on the builder (all flow into `data-s3-config`):
`->fuzzy()`, `->searchGroups()`, `->searchField(['name', 'email'])`,
`->debounce(300)`, `->minChars(2)`, `->maxRender(50)`, `->theme('bootstrap5')`.

## Blade component

```blade
<x-select3 name="country" placeholder="Pick" :options="['au' => 'Australia', 'nz' => 'New Zealand']" />
<x-select3 name="tags[]" multiple tags :selected="$current" :config="['maxItems' => 5]" />
```

## AJAX search endpoint

```php
use Ozankurt\Select3\Search\Select3Search;

Route::get('/users/search', function (Request $request) {
    return Select3Search::make(\App\Models\User::query())
        ->searchable(['name', 'email'])
        ->label('name')->value('id')
        ->extra(fn ($u) => ['image' => $u->avatar_url, 'description' => $u->email])
        ->perPage(config('select3.per_page'))
        ->get($request->query('q', ''), (int) $request->query('page', 1));
});
```

Returns `{ "results": [{ "value", "label", "image", "description" }], "hasMore": bool }` —
exactly the shape select3's AJAX adapter consumes. Point the builder at it with
`->ajax(route('users.search'))`.

Swap `->searchable([...])` for `->fulltext(['name', 'bio'])` to use a FULLTEXT
index instead of LIKE, and add `->group('team')` (column or closure) to return
grouped results that the JS renders as `<optgroup>`s.

## The JS enhancer contract

The builder emits a `data-s3-config` JSON blob (placeholder, searchable, tags,
maxItems, theme, locale, …). Have your enhancer forward it to `createSelect3`:

```js
import createSelect3 from '@ozankurt/select3';

document.querySelectorAll('select[data-select3]').forEach((el) => {
    const config = el.dataset.s3Config ? JSON.parse(el.dataset.s3Config) : {};
    createSelect3(el, config);
});
```

## License

MIT © Ozan Kurt
