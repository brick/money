<?php

declare(strict_types=1);

namespace Brick\Money;

/**
 * Determines how the remainder is handled when splitting a Money into equal parts.
 */
enum SplitMode
{
    /**
     * Each part receives a floor equal share; the remainder is distributed one step at a time to the first parts.
     *
     * For example, splitting `USD 1.00` into 3 parts yields `[USD 0.34, USD 0.33, USD 0.33]`.
     */
    case ToFirst;

    /**
     * Each part receives a floor equal share; the remainder is returned as the last element.
     *
     * For example, splitting `USD 1.00` into 3 parts yields `[USD 0.33, USD 0.33, USD 0.33, USD 0.01]`.
     */
    case Separate;
}
