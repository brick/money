<?php

declare(strict_types=1);

namespace Brick\Money\Allocation;

use Brick\Math\BigInteger;

/**
 * @internal
 */
interface AllocationStrategy
{
    /**
     * Allocates a non-negative amount (expressed as an integer step count) according to the given ratios.
     *
     * The caller guarantees that $amountInSteps is non-negative. Sign handling and scale/step conversion
     * are done in Money::allocate().
     *
     * @param list<BigInteger> $ratios
     *
     * @return list<BigInteger>
     *
     * @pure
     */
    public function allocate(BigInteger $amountInSteps, array $ratios): array;
}
