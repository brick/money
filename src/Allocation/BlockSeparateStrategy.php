<?php

declare(strict_types=1);

namespace Brick\Money\Allocation;

use Brick\Math\BigInteger;

/**
 * @internal
 */
final class BlockSeparateStrategy implements AllocationStrategy
{
    /**
     * @param list<BigInteger> $ratios
     *
     * @return list<BigInteger>
     */
    public function allocate(BigInteger $amountInSteps, array $ratios): array
    {
        $total = BigInteger::sum(...$ratios);

        [$quotient, $remainderSteps] = $amountInSteps->quotientAndRemainder($total);

        $allSteps = [];

        foreach ($ratios as $ratio) {
            $allSteps[] = $quotient->multipliedBy($ratio);
        }

        $allSteps[] = $remainderSteps;

        return $allSteps;
    }
}
