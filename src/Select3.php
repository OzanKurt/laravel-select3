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

    /**
     * A searchable, region-grouped timezone picker.
     *
     *   echo Select3::timezones('timezone')->pin('inherit', 'Same as Acme (Europe/London)');
     */
    public static function timezones(string $name = 'timezone'): Builder
    {
        return Builder::make($name)->timezones();
    }
}
