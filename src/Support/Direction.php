<?php

declare(strict_types=1);

namespace PlinCode\EloquentSorts\Support;

use InvalidArgumentException;

final class Direction
{
    /**
     * Laravel validates the direction passed to orderBy(), but not the one
     * passed to orderByRaw(). Every order in this package goes through here
     * so a direction can never reach raw SQL unchecked.
     *
     * @throws InvalidArgumentException
     */
    public static function normalise(string $direction): string
    {
        $normalised = strtolower(trim($direction));

        if ($normalised !== 'asc' && $normalised !== 'desc') {
            throw new InvalidArgumentException(
                "Order direction must be 'asc' or 'desc', [{$direction}] given.",
            );
        }

        return $normalised;
    }
}
