<?php

namespace Ozankurt\Select3;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

/**
 * Builds select3 option rows for every IANA timezone, grouped by region
 * ("Africa", "Europe", ...) with the city as the label and the current UTC
 * offset as right-aligned meta text. Offsets are computed at $at (default:
 * now), so they reflect daylight saving time on that date.
 *
 *   Timezones::options();                    // every zone
 *   Timezones::options(['Europe', 'Asia']);  // only those regions
 */
class Timezones
{
    /**
     * @param  list<string>|null  $regions  Region prefixes to keep (e.g. ['Europe']); null keeps all.
     * @return list<array{value:string,label:string,group:string,meta:string,data:array<string,string>}>
     */
    public static function options(?array $regions = null, ?DateTimeInterface $at = null): array
    {
        $at = DateTimeImmutable::createFromInterface($at ?? new DateTimeImmutable('now'));
        $rows = [];

        foreach (DateTimeZone::listIdentifiers() as $identifier) {
            $parts = explode('/', $identifier);
            $region = count($parts) > 1 ? $parts[0] : 'UTC';
            if ($regions !== null && ! in_array($region, $regions, true)) {
                continue;
            }

            $local = $at->setTimezone(new DateTimeZone($identifier));
            $offset = $local->format('P');

            // Keywords widen search beyond the city label: the raw identifier,
            // the region, a "UTC+03:00" form, and a letter abbreviation like
            // "BST" when PHP knows one (it reports "+03" for zones without).
            $keywords = [$identifier, str_replace('_', ' ', $identifier), 'UTC' . $offset, 'GMT' . $offset];
            $abbreviation = $local->format('T');
            if (preg_match('/^[A-Za-z]+$/', $abbreviation) === 1) {
                $keywords[] = $abbreviation;
            }

            $rows[] = [
                'value' => $identifier,
                'label' => static::label($parts),
                'group' => $region,
                'meta' => $offset,
                'data' => ['keywords' => implode(' ', array_unique($keywords))],
            ];
        }

        usort($rows, static fn (array $a, array $b) => [$a['group'], $a['label']] <=> [$b['group'], $b['label']]);

        return $rows;
    }

    /**
     * Human label for a split identifier: "Europe/London" => "London",
     * "America/Argentina/Buenos_Aires" => "Buenos Aires, Argentina".
     *
     * @param  list<string>  $parts
     */
    protected static function label(array $parts): string
    {
        $parts = array_map(static fn (string $p) => str_replace('_', ' ', $p), $parts);
        if (count($parts) === 1) {
            return $parts[0];
        }
        $city = $parts[count($parts) - 1];
        $middle = array_slice($parts, 1, -1);

        return $middle === [] ? $city : $city . ', ' . implode(', ', array_reverse($middle));
    }
}
