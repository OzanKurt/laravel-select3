<?php

namespace Ozankurt\Select3;

/**
 * Entry point for the fluent builder.
 *
 *   use Ozankurt\Select3\Select3;
 *   echo Select3::make('country')->placeholder('Pick')->options(['au' => 'Australia']);
 */
class Select3
{
    public static function make(string $name): Builder
    {
        return Builder::make($name);
    }
}
