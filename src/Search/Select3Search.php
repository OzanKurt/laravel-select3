<?php

namespace Ozankurt\Select3\Search;

use Closure;
use Illuminate\Support\Collection;

/**
 * Turns a `?q=&page=` request into the `{ results, hasMore }` shape select3's
 * AJAX adapter expects, with searchable columns, pagination, and per-row data
 * (icon/image/description/badge). Accepts an Eloquent builder or relation.
 *
 *   Select3Search::make(User::query())
 *       ->searchable(['name', 'email'])
 *       ->label('name')->value('id')
 *       ->extra(fn ($u) => ['image' => $u->avatar_url, 'description' => $u->email])
 *       ->perPage(20)
 *       ->get($request->query('q', ''), (int) $request->query('page', 1));
 */
class Select3Search
{
    /** @var list<string> */
    protected array $searchable = [];
    /** @var list<string> */
    protected array $fulltext = [];
    protected string|Closure $label = 'name';
    protected string $value = 'id';
    protected ?Closure $extra = null;
    protected string|Closure|null $group = null;
    protected int $perPage = 20;

    public function __construct(protected mixed $query) {}

    public static function make(mixed $query): static
    {
        return new static($query);
    }

    /** @param list<string> $columns */
    public function searchable(array $columns): static
    {
        $this->searchable = $columns;
        return $this;
    }

    /**
     * Use a MySQL/PostgreSQL FULLTEXT match on these columns instead of LIKE.
     * Requires a fulltext index. Takes precedence over searchable() when set.
     *
     * @param list<string> $columns
     */
    public function fulltext(array $columns): static
    {
        $this->fulltext = $columns;
        return $this;
    }

    /** Group results (column or closure) so the JS renders remote <optgroup>s. */
    public function group(string|Closure $group): static
    {
        $this->group = $group;
        return $this;
    }

    public function label(string|Closure $label): static
    {
        $this->label = $label;
        return $this;
    }

    public function value(string $value): static
    {
        $this->value = $value;
        return $this;
    }

    public function extra(Closure $extra): static
    {
        $this->extra = $extra;
        return $this;
    }

    public function perPage(int $perPage): static
    {
        $this->perPage = max(1, $perPage);
        return $this;
    }

    /**
     * Run the search and return `['results' => [...], 'hasMore' => bool]`.
     * Fetches one extra row to detect whether further pages exist.
     */
    public function get(string $q = '', int $page = 1): array
    {
        if (! is_object($this->query)) {
            throw new \InvalidArgumentException('Select3Search::get() requires an Eloquent builder or relation.');
        }

        $query = clone $this->query;
        $q = trim($q);

        if ($q !== '') {
            if ($this->fulltext !== []) {
                // Fulltext (requires an index); takes precedence over LIKE.
                $query->whereFullText($this->fulltext, $q);
            } elseif ($this->searchable !== []) {
                // Escape LIKE wildcards so a query of "%" or "_" can't broaden the
                // match to every row (default escape "\" works on MySQL/PgSQL/SQLite).
                $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q);
                $query->where(function ($sub) use ($escaped): void {
                    foreach ($this->searchable as $column) {
                        $sub->orWhere($column, 'like', '%' . $escaped . '%');
                    }
                });
            }
        }

        $offset = max(0, ($page - 1) * $this->perPage);
        $rows = collect($query->skip($offset)->take($this->perPage + 1)->get());

        return $this->shape($rows);
    }

    /**
     * Map a fetched result set (sized perPage + 1) into the response shape.
     * Pure and side-effect free, so it is unit-testable with a plain Collection.
     */
    public function shape(Collection $rows): array
    {
        $hasMore = $rows->count() > $this->perPage;

        $results = $rows
            ->take($this->perPage)
            ->map(function ($model): array {
                $label = $this->label instanceof Closure
                    ? ($this->label)($model)
                    : data_get($model, $this->label);

                $row = [
                    'value' => (string) data_get($model, $this->value),
                    'label' => (string) $label,
                ];

                if ($this->group !== null) {
                    $row['group'] = (string) ($this->group instanceof Closure
                        ? ($this->group)($model)
                        : data_get($model, $this->group));
                }

                if ($this->extra !== null) {
                    $row = array_merge($row, (array) ($this->extra)($model));
                }

                return $row;
            })
            ->values()
            ->all();

        return ['results' => $results, 'hasMore' => $hasMore];
    }
}
