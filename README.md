# ozankurt/laravel-select3

[![packagist](https://img.shields.io/packagist/v/ozankurt/laravel-select3.svg)](https://packagist.org/packages/ozankurt/laravel-select3)
[![php](https://img.shields.io/packagist/php-v/ozankurt/laravel-select3.svg)](https://packagist.org/packages/ozankurt/laravel-select3)
[![license](https://img.shields.io/packagist/l/ozankurt/laravel-select3.svg)](./LICENSE.md)

Laravel companion for [`@ozankurt/select3`](https://github.com/OzanKurt/select3). It
generates the markup, config, and AJAX responses the JS enhancer reads, so you
build rich, **server-driven** dropdowns in PHP — a fluent builder, a Blade
component, and an AJAX search endpoint with per-row icons / images / badges /
groups.

The JS package stays the source of truth; this package only emits what it reads.

## Contents

- [Install](#install)
- [Fluent builder](#fluent-builder)
- [Blade component](#blade-component)
- [AJAX search endpoint](#ajax-search-endpoint)
- [Builder reference](#builder-reference)
- [Select3Search reference](#select3search-reference)
- [Configuration](#configuration)
- [Wiring the JS enhancer](#wiring-the-js-enhancer)

---

## Install

```bash
composer require ozankurt/laravel-select3
php artisan vendor:publish --tag=select3-config   # optional: config/select3.php
```

Requires the JS side installed and enhanced (see [`@ozankurt/select3`](https://github.com/OzanKurt/select3)).
Auto-discovery registers the service provider and the `<x-select3>` component.

## Fluent builder

```php
use Ozankurt\Select3\Select3;

echo Select3::make('country')
    ->placeholder('Pick a country')
    ->searchable()
    ->fuzzy()
    ->options(['au' => 'Australia', 'nz' => 'New Zealand']);
```

From a model, with rich per-row data and grouping:

```php
echo Select3::make('user_id')
    ->fromModel(
        \App\Models\User::query()->orderBy('name'),
        label: 'name',
        value: 'id',
        extra: fn ($u) => ['image' => $u->avatar_url, 'description' => $u->email, 'group' => $u->team],
    )
    ->placeholder('Assign a user')
    ->multiple()
    ->maxItems(3);
```

Renders a native `<select data-select3 data-s3-config="{…}">` with `<option>`s
carrying `data-icon` / `data-image` / `data-description` / `data-badge`, grouped
into `<optgroup>`s.

## Blade component

```blade
<x-select3 name="country" placeholder="Pick" :options="['au' => 'Australia', 'nz' => 'New Zealand']" />

<x-select3 name="tags" multiple tags :selected="$current" :config="['maxItems' => 5, 'fuzzy' => true]" />
```

| Prop | Type | Default | Description |
|---|---|---|---|
| `name` | `string` | — | Field name (required). `[]` is added for `multiple`. |
| `options` | `array` | `[]` | `[value => label]` or list of option arrays. |
| `multiple` | `bool` | `false` | Multi-select. |
| `placeholder` | `?string` | `null` | Empty-state text. |
| `tags` | `bool` | `false` | Allow creating options. |
| `ajax` | `?string` | `null` | Remote endpoint URL. |
| `selected` | `mixed` | `null` | Preselected value(s). |
| `config` | `array` | `[]` | Extra options forwarded into `data-s3-config`. |

## AJAX search endpoint

```php
use Ozankurt\Select3\Search\Select3Search;

Route::get('/users/search', function (Request $request) {
    return Select3Search::make(\App\Models\User::query())
        ->searchable(['name', 'email'])           // or ->fulltext(['name', 'bio'])
        ->label('name')->value('id')
        ->group('team')                            // optional: render <optgroup>s
        ->extra(fn ($u) => ['image' => $u->avatar_url, 'description' => $u->email])
        ->perPage(config('select3.per_page'))
        ->get($request->query('q', ''), (int) $request->query('page', 1));
})->name('users.search');
```

Returns the exact shape select3's AJAX adapter consumes:

```json
{ "results": [{ "value": "1", "label": "Ada", "image": "…", "group": "Core" }], "hasMore": true }
```

Point a builder at it with `->ajax(route('users.search'))`. LIKE wildcards in the
query are escaped; swap `searchable()` for `fulltext()` to use a FULLTEXT index.

## Builder reference

| Method | Description |
|---|---|
| `make($name)` | Create a builder. |
| `options($array)` / `option($value, $label, $extra)` | Add options (assoc map or option arrays). |
| `fromModel($query, $label, $value, $extra)` | Build options from an Eloquent query/class. |
| `selected($value\|$array)` | Preselect value(s). |
| `multiple()` · `tags()` · `searchable()` · `required()` | Toggle flags. |
| `placeholder()` · `id()` · `theme()` · `locale()` | Display + i18n. |
| `ajax($url)` | Remote endpoint. |
| `fuzzy()` · `searchGroups()` · `searchField([...])` | Search tuning. |
| `maxItems()` · `debounce()` · `minChars()` · `maxRender()` | Limits (flow into `data-s3-config`). |
| `attrs([...])` · `config([...])` | Extra HTML attrs / extra JS options. |
| `toHtml()` · `(string)` · `toConfig()` | Render / inspect the config. |

## Select3Search reference

| Method | Description |
|---|---|
| `make($query)` | From an Eloquent builder or relation. |
| `searchable([...])` | Columns to `LIKE` (wildcards escaped). |
| `fulltext([...])` | Columns for `whereFullText` (takes precedence). |
| `label($column\|$closure)` · `value($column)` | Result label / value. |
| `group($column\|$closure)` | Add a `group` to each row → JS `<optgroup>`. |
| `extra($closure)` | Merge extra per-row data (icon/image/…). |
| `perPage($n)` | Page size. |
| `get($q, $page)` | Run it → `['results' => [...], 'hasMore' => bool]`. |

## Configuration

`config/select3.php` (publish with `--tag=select3-config`):

| Key | Default | Used by |
|---|---|---|
| `theme` | `null` | default theme passed to builders |
| `locale` | `null` | default i18n pack |
| `searchable` | `true` | default search-box visibility |
| `debounce` | `250` | default AJAX debounce (ms) |
| `min_chars` | `1` | default chars before searching |
| `per_page` | `20` | default `Select3Search` page size |

## Wiring the JS enhancer

The builder emits a `data-s3-config` JSON blob. Have your enhancer parse it and
forward it to `createSelect3`:

```js
import createSelect3 from '@ozankurt/select3';
import '@ozankurt/select3/css';

document.querySelectorAll('select[data-select3]').forEach((el) => {
  const config = el.dataset.s3Config ? JSON.parse(el.dataset.s3Config) : {};
  createSelect3(el, config);
});
```

## License

MIT © Ozan Kurt
