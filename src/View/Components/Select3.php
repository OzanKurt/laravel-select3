<?php

namespace Ozankurt\Select3\View\Components;

use Illuminate\Support\HtmlString;
use Illuminate\View\Component;
use Ozankurt\Select3\Builder;

/**
 * <x-select3 name="country" :options="$countries" placeholder="Pick" /> — a thin
 * Blade wrapper over {@see Builder}. Returns an HtmlString so the rendered
 * <select> markup is emitted verbatim (never re-compiled as a Blade template).
 */
class Select3 extends Component
{
    public function __construct(
        public string $name,
        public array $options = [],
        public bool $multiple = false,
        public ?string $placeholder = null,
        public bool $tags = false,
        public ?string $ajax = null,
        public mixed $selected = null,
        public array $config = [],
    ) {}

    public function render(): HtmlString
    {
        $builder = Builder::make($this->name)
            ->multiple($this->multiple)
            ->options($this->options)
            ->tags($this->tags)
            ->ajax($this->ajax)
            ->config($this->config);

        if ($this->placeholder !== null) {
            $builder->placeholder($this->placeholder);
        }
        if ($this->selected !== null) {
            $builder->selected($this->selected);
        }

        return new HtmlString($builder->toHtml());
    }
}
