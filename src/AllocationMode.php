<?php

declare(strict_types=1);

namespace Brick\Money;

/**
 * Determines the allocation algorithm and how the remainder is handled.
 */
enum AllocationMode
{
    /**
     * Each allocatee receives a proportional floor amount; the remainder is distributed unit by unit to the first
     * allocatees in order (Martin Fowler method).
     */
    case FloorToFirst;

    /**
     * Each allocatee receives a proportional floor amount; the remainder is distributed unit by unit to the
     * allocatees with the largest fractional remainders (Hamilton method). Ties are broken by original index.
     */
    case FloorToLargestRemainder;

    /**
     * Each allocatee receives a proportional floor amount; the remainder is distributed unit by unit to the
     * allocatees with the largest ratios. Ties are broken by original index.
     */
    case FloorToLargestRatio;

    /**
     * Each allocatee receives a proportional floor amount; the remainder is returned as the last element.
     */
    case FloorSeparate;

    /**
     * Each allocatee receives an exact multiple of their ratio (only complete blocks of sum(normalized ratios) base
     * units are allocated); the remainder is returned as the last element. Ratios are normalized to their lowest
     * integer form before allocation, so e.g. [2, 4] and [1, 2] produce identical results.
     */
    case BlockSeparate;
}
