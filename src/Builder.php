<?php

namespace Ozankurt\Select3;

use Closure;
use Illuminate\Contracts\Support\Htmlable;
use Stringable;

/**
 * Fluent builder that renders a native <select data-select3> plus a
 * data-s3-config JSON blob the JS enhancer reads. HTML is assembled with
 * htmlspecialchars (no Blade dependency), so option labels/values can never
 * inject markup and the builder is unit-testable without booting Laravel.
 */
class Builder implements Htmlable, Stringable
{
    protected bool $multiple = false;
    protected ?string $placeholder = null;
    protected bool $searchable = true;
    protected bool $tags = false;
    protected ?string $ajax = null;
    protected ?int $maxItems = null;
    protected ?string $theme = null;
    protected ?string $locale = null;
    protected bool $fuzzy = false;
    protected bool $searchGroups = false;
    protected ?int $debounce = null;
    protected ?int $minChars = null;
    protected ?int $maxRender = null;
    /** @var list<string>|null */
    protected ?array $searchField = null;
    protected bool $required = false;
    protected ?string $id = null;

    /** @var list<array<string,mixed>> */
    protected array $options = [];
    /** @var array<string,bool> */
    protected array $selected = [];
    /** @var array<string,mixed> */
    protected array $config = [];
    /** @var array<string,string> */
    protected array $attributes = [];

    public function __construct(public string $name) {}

    public static function make(string $name): static
    {
        return new static($name);
    }

    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;
        return $this;
    }

    public function placeholder(?string $text): static
    {
        $this->placeholder = $text;
        return $this;
    }

    public function searchable(bool $on = true): static
    {
        $this->searchable = $on;
        return $this;
    }

    public function tags(bool $on = true): static
    {
        $this->tags = $on;
        return $this;
    }

    public function ajax(?string $url): static
    {
        $this->ajax = $url;
        return $this;
    }

    public function maxItems(?int $n): static
    {
        $this->maxItems = $n;
        return $this;
    }

    public function fuzzy(bool $on = true): static
    {
        $this->fuzzy = $on;
        return $this;
    }

    public function searchGroups(bool $on = true): static
    {
        $this->searchGroups = $on;
        return $this;
    }

    public function debounce(?int $ms): static
    {
        $this->debounce = $ms;
        return $this;
    }

    public function minChars(?int $n): static
    {
        $this->minChars = $n;
        return $this;
    }

    public function maxRender(?int $n): static
    {
        $this->maxRender = $n;
        return $this;
    }

    /** @param list<string> $fields */
    public function searchField(array $fields): static
    {
        $this->searchField = $fields;
        return $this;
    }

    public function theme(?string $theme): static
    {
        $this->theme = $theme;
        return $this;
    }

    public function locale(?string $locale): static
    {
        $this->locale = $locale;
        return $this;
    }

    public function required(bool $on = true): static
    {
        $this->required = $on;
        return $this;
    }

    public function id(?string $id): static
    {
        $this->id = $id;
        return $this;
    }

    /** @param array<string,string> $attrs */
    public function attrs(array $attrs): static
    {
        $this->attributes = array_merge($this->attributes, $attrs);
        return $this;
    }

    /** Extra select3 options forwarded verbatim into data-s3-config. */
    public function config(array $config): static
    {
        $this->config = array_merge($this->config, $config);
        return $this;
    }

    /** Preselect one value or a list of values. */
    public function selected(string|int|array $value): static
    {
        foreach ((array) $value as $v) {
            $this->selected[(string) $v] = true;
        }
        return $this;
    }

    /**
     * Pin existing option values above every optgroup, in the given order.
     *
     * @param  string|int|list<string|int>  $values
     */
    public function pinned(string|int|array $values): static
    {
        $current = $this->config['pinned'] ?? [];
        foreach ((array) $values as $v) {
            $current[] = (string) $v;
        }
        $this->config['pinned'] = array_values(array_unique($current));
        return $this;
    }

    /**
     * Add an option pinned above every optgroup, e.g. an "inherit" default:
     *   ->pin('inherit', 'Same as Acme (Europe/London)')
     *
     * @param  array<string,mixed>  $extra
     */
    public function pin(string $value, string $label, array $extra = []): static
    {
        // Ungrouped and listed first so the native <select> (and the no-JS or
        // mobile fallback) also shows it at the top.
        $this->option($value, $label, ['pinned' => true, 'group' => null] + $extra);
        array_unshift($this->options, array_pop($this->options));
        return $this;
    }

    /**
     * Fill the select with every IANA timezone, grouped by region, with the UTC
     * offset as meta text. Search matches the city, region, identifier,
     * "UTC+03:00" style offsets and abbreviations such as "BST".
     *
     * @param  list<string>|null  $regions  Limit to these regions (e.g. ['Europe']).
     */
    public function timezones(?array $regions = null, ?\DateTimeInterface $at = null): static
    {
        $this->options(Timezones::options($regions, $at));
        $this->searchField ??= ['label', 'meta', 'keywords'];
        $this->searchGroups = true;
        return $this;
    }

    /**
     * Add options. Accepts an associative [value => label] map, or a list of
     * arrays each carrying value + label and optional
     * icon/image/description/badge/meta/pinned/disabled/group, plus a `data`
     * map of extra data-* attributes (e.g. ['keywords' => 'uk gb']).
     */
    public function options(array $options): static
    {
        if (array_is_list($options)) {
            foreach ($options as $o) {
                $this->option((string) ($o['value'] ?? ''), (string) ($o['label'] ?? ''), $o);
            }
        } else {
            foreach ($options as $value => $label) {
                $this->option((string) $value, (string) $label);
            }
        }
        return $this;
    }

    /** @param array<string,mixed> $extra */
    public function option(string $value, string $label, array $extra = []): static
    {
        $this->options[] = [
            'value' => $value,
            'label' => $label,
            'icon' => $extra['icon'] ?? null,
            'image' => $extra['image'] ?? null,
            'description' => $extra['description'] ?? null,
            'badge' => $extra['badge'] ?? null,
            'meta' => $extra['meta'] ?? null,
            'pinned' => (bool) ($extra['pinned'] ?? false),
            'disabled' => (bool) ($extra['disabled'] ?? false),
            'group' => $extra['group'] ?? null,
            'data' => (array) ($extra['data'] ?? []),
        ];
        return $this;
    }

    /**
     * Build options from an Eloquent model class or query. $label/$value are
     * columns ($label may also be a closure); $extra maps a model to its
     * icon/image/description/badge bag.
     */
    public function fromModel(mixed $query, string|Closure $label = 'name', string $value = 'id', ?Closure $extra = null): static
    {
        $query = is_string($query) ? $query::query() : $query;
        foreach ($query->get() as $model) {
            $text = $label instanceof Closure ? $label($model) : data_get($model, $label);
            $this->option(
                (string) data_get($model, $value),
                (string) $text,
                $extra ? (array) $extra($model) : [],
            );
        }
        return $this;
    }

    /** The select3 option bag emitted as data-s3-config for the JS enhancer. */
    public function toConfig(): array
    {
        $base = [
            'placeholder' => $this->placeholder,
            'searchable' => $this->searchable,
            'tags' => $this->tags ?: null,
            'ajax' => $this->ajax,
            'maxItems' => $this->maxItems,
            'theme' => $this->theme,
            'locale' => $this->locale,
            'fuzzy' => $this->fuzzy ?: null,
            'searchGroups' => $this->searchGroups ?: null,
            'debounce' => $this->debounce,
            'minChars' => $this->minChars,
            'maxRender' => $this->maxRender,
            'searchField' => $this->searchField,
        ];

        // User-supplied config() overrides the built-ins (array_merge, not +).
        return array_filter(array_merge($base, $this->config), static fn ($v) => $v !== null);
    }

    public function toHtml(): string
    {
        // Normalize a name that already ends in [] so multi-selects don't become
        // name="x[][]" (which PHP would parse as a nested array). The id mirrors
        // the bare name (square brackets are invalid in an HTML id / CSS selector).
        $base = preg_replace('/\[\]$/', '', $this->name) ?? $this->name;
        $attrs = [
            'name' => $base . ($this->multiple ? '[]' : ''),
            'id' => $this->id ?? $base,
            'class' => 'form-control',
            'data-select3' => '',
            'data-s3-config' => json_encode($this->toConfig(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        ];
        if ($this->multiple) {
            $attrs['multiple'] = '';
        }
        if ($this->required) {
            $attrs['required'] = '';
        }
        if ($this->tags) {
            $attrs['data-tags'] = '';
        }
        if ($this->ajax !== null) {
            $attrs['data-ajax'] = $this->ajax;
        }
        if ($this->placeholder !== null) {
            $attrs['data-placeholder'] = $this->placeholder;
        }
        $attrs = array_merge($attrs, $this->attributes);

        $html = '<select' . $this->attrString($attrs) . '>';
        if ($this->placeholder !== null && ! $this->multiple) {
            $html .= '<option value=""></option>';
        }

        // Render options in order, opening an <optgroup> whenever the group changes.
        $currentGroup = null;
        $groupOpen = false;
        foreach ($this->options as $o) {
            $group = $o['group'] ?? null;
            if ($group !== $currentGroup) {
                if ($groupOpen) {
                    $html .= '</optgroup>';
                    $groupOpen = false;
                }
                if ($group !== null) {
                    $html .= '<optgroup label="' . $this->e((string) $group) . '">';
                    $groupOpen = true;
                }
                $currentGroup = $group;
            }
            $html .= $this->optionHtml($o);
        }
        if ($groupOpen) {
            $html .= '</optgroup>';
        }

        return $html . '</select>';
    }

    /** @param array<string,mixed> $o */
    protected function optionHtml(array $o): string
    {
        $attrs = ['value' => (string) $o['value']];
        foreach (['icon', 'image', 'description', 'badge', 'meta'] as $key) {
            if (! empty($o[$key])) {
                $attrs['data-' . $key] = (string) $o[$key];
            }
        }
        foreach ($o['data'] ?? [] as $key => $value) {
            // Only safe attribute-name characters; values are escaped by attrString().
            if (preg_match('/^[a-z][a-z0-9-]*$/', (string) $key) === 1 && $value !== null && $value !== '') {
                $attrs['data-' . $key] = (string) $value;
            }
        }
        if (! empty($o['pinned'])) {
            $attrs['data-pinned'] = '';
        }
        if (! empty($o['disabled'])) {
            $attrs['disabled'] = '';
        }
        if (isset($this->selected[(string) $o['value']])) {
            $attrs['selected'] = '';
        }

        return '<option' . $this->attrString($attrs) . '>' . $this->e((string) $o['label']) . '</option>';
    }

    /** @param array<string,string> $attrs */
    protected function attrString(array $attrs): string
    {
        $out = '';
        foreach ($attrs as $key => $value) {
            $out .= $value === ''
                ? ' ' . $key // boolean attribute
                : ' ' . $key . '="' . $this->e($value) . '"';
        }
        return $out;
    }

    protected function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    public function __toString(): string
    {
        return $this->toHtml();
    }
}
