<?php

declare(strict_types=1);

namespace Brick\Money;

/**
 * Determines how the remainder is handled when splitting a Money into equal parts.
 */
enum SplitMode
{
    /**
     * Each part receives floor(amount / n); the remainder is distributed unit by unit to the first parts.
     */
    case ToFirst;

    /**
     * Each part receives floor(amount / n); the remainder is returned as the last element.
     */
    case Separate;
}
