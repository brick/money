<?php

declare(strict_types=1);

namespace Brick\Money;

use Brick\Money\Allocation\AllocationStrategy;
use Brick\Money\Allocation\BlockSeparateStrategy;
use Brick\Money\Allocation\FloorSeparateStrategy;
use Brick\Money\Allocation\FloorToFirstStrategy;
use Brick\Money\Allocation\FloorToLargestRatioStrategy;
use Brick\Money\Allocation\FloorToLargestRemainderStrategy;

/**
 * Determines the allocation algorithm and how the remainder is handled.
 */
enum AllocationMethod
{
    /**
     * Each allocatee receives a proportional floor amount; the remainder is distributed one step at a time to the
     * first allocatees in order (Martin Fowler method).
     *
     * For example, allocating `USD 1.00` by `[2, 3, 1]` yields `[USD 0.34, USD 0.50, USD 0.16]`.
     */
    case FloorToFirst;

    /**
     * Each allocatee receives a proportional floor amount; the remainder is distributed one step at a time to the
     * allocatees with the largest fractional remainders (Hamilton method). Ties are broken by original index.
     *
     * For example, allocating `USD 1.00` by `[2, 3, 1]` yields `[USD 0.33, USD 0.50, USD 0.17]`.
     */
    case FloorToLargestRemainder;

    /**
     * Each allocatee receives a proportional floor amount; the remainder is distributed one step at a time to the
     * allocatees with the largest ratios. Ties are broken by original index.
     *
     * For example, allocating `USD 1.00` by `[2, 3, 1]` yields `[USD 0.33, USD 0.51, USD 0.16]`.
     */
    case FloorToLargestRatio;

    /**
     * Each allocatee receives a proportional floor amount; the remainder is returned as the last element.
     *
     * For example, allocating `USD 1.00` by `[2, 3, 1]` yields `[USD 0.33, USD 0.50, USD 0.16, USD 0.01]`.
     */
    case FloorSeparate;

    /**
     * Each allocatee receives an exact multiple of their normalized ratio; only complete blocks of
     * sum(normalized ratios) steps are allocated, and the remainder is returned as the last element.
     *
     * Ratios are normalized to their lowest integer form before allocation, so e.g. [2, 4] and [1, 2] produce
     * identical results.
     *
     * For example, allocating `USD 1.00` by `[2, 3, 1]` yields `[USD 0.32, USD 0.48, USD 0.16, USD 0.04]`.
     */
    case BlockSeparate;

    /**
     * @internal
     *
     * @pure
     */
    public function getStrategy(): AllocationStrategy
    {
        return match ($this) {
            AllocationMethod::FloorToFirst => new FloorToFirstStrategy(),
            AllocationMethod::FloorToLargestRemainder => new FloorToLargestRemainderStrategy(),
            AllocationMethod::FloorToLargestRatio => new FloorToLargestRatioStrategy(),
            AllocationMethod::FloorSeparate => new FloorSeparateStrategy(),
            AllocationMethod::BlockSeparate => new BlockSeparateStrategy(),
        };
    }
}
