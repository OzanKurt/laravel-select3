<?php

use Illuminate\Support\Collection;
use Ozankurt\Select3\Search\Select3Search;

/**
 * A minimal query-builder double. It records where/orWhere/skip/take into a
 * shared stdClass (shared by reference across clone, so Select3Search::get()'s
 * `clone` doesn't hide the calls) and returns a preset row collection.
 */
class FakeQuery
{
    public function __construct(
        public Collection $rows,
        public stdClass $log,
    ) {}

    public function where(callable $cb): static
    {
        $this->log->whereCalled = true;
        $cb($this);
        return $this;
    }

    public function orWhere(string $column, ?string $op = null, mixed $value = null): static
    {
        $this->log->orWhere[] = [$column, $op, $value];
        return $this;
    }

    public function skip(int $n): static
    {
        $this->log->skip = $n;
        return $this;
    }

    public function take(int $n): static
    {
        $this->log->take = $n;
        return $this;
    }

    /** @param list<string> $columns */
    public function whereFullText(array $columns, string $value): static
    {
        $this->log->fullText = [$columns, $value];
        return $this;
    }

    public function get(): Collection
    {
        return $this->rows;
    }
}

function freshLog(): stdClass
{
    $log = new stdClass();
    $log->orWhere = [];
    $log->whereCalled = false;
    $log->fullText = null;
    return $log;
}

it('applies search, pagination, and shapes results in get()', function () {
    $log = freshLog();
    $rows = collect([
        ['id' => 1, 'name' => 'Ann'],
        ['id' => 2, 'name' => 'Bob'],
        ['id' => 3, 'name' => 'Cat'], // perPage + 1 sentinel
    ]);

    $out = Select3Search::make(new FakeQuery($rows, $log))
        ->searchable(['name', 'email'])->label('name')->value('id')->perPage(2)
        ->get('an', 2);

    expect($log->whereCalled)->toBeTrue();
    expect($log->orWhere)->toHaveCount(2);          // one clause per searchable column
    expect($log->orWhere[0][2])->toBe('%an%');      // LIKE value
    expect($log->skip)->toBe(2);                    // (page 2 - 1) * perPage 2
    expect($log->take)->toBe(3);                    // perPage + 1
    expect($out['hasMore'])->toBeTrue();
    expect($out['results'])->toHaveCount(2);
    expect($out['results'][0])->toEqual(['value' => '1', 'label' => 'Ann']);
});

it('skips the where clause when the query string is empty', function () {
    $log = freshLog();
    Select3Search::make(new FakeQuery(collect([]), $log))->searchable(['name'])->perPage(5)->get('', 1);

    expect($log->whereCalled)->toBeFalse();
    expect($log->skip)->toBe(0);
    expect($log->take)->toBe(6);
});

it('escapes LIKE wildcards in the search term', function () {
    $log = freshLog();
    Select3Search::make(new FakeQuery(collect([]), $log))->searchable(['name'])->get('%_x', 1);

    expect($log->orWhere[0][2])->toBe('%\\%\\_x%');
});

it('clamps perPage to at least 1', function () {
    $log = freshLog();
    Select3Search::make(new FakeQuery(collect([]), $log))->perPage(0)->get('', 1);

    expect($log->take)->toBe(2); // 1 + 1
});

it('throws when get() is called without an object query', function () {
    expect(fn () => Select3Search::make(null)->get('x'))->toThrow(InvalidArgumentException::class);
});

it('uses whereFullText (not LIKE) when fulltext columns are set', function () {
    $log = freshLog();
    Select3Search::make(new FakeQuery(collect([]), $log))
        ->fulltext(['title', 'body'])->get('hello', 1);

    expect($log->fullText)->toBe([['title', 'body'], 'hello']);
    expect($log->whereCalled)->toBeFalse();
});
