<?php

declare(strict_types=1);

namespace Brick\Money\Allocation;

use Brick\Math\BigInteger;

/**
 * @internal
 */
abstract class AbstractFloorStrategy implements AllocationStrategy
{
    /**
     * Returns the floor step counts and the remainder for each ratio.
     *
     * @param list<BigInteger> $ratios
     *
     * @return array{list<BigInteger>, BigInteger} [floors, remainderSteps]
     *
     * @pure
     */
    final protected function computeFloors(BigInteger $amountInSteps, array $ratios): array
    {
        $total = BigInteger::sum(...$ratios);
        $floors = [];
        $totalFloors = BigInteger::zero();

        foreach ($ratios as $ratio) {
            $f = $amountInSteps->multipliedBy($ratio)->quotient($total);
            $floors[] = $f;
            $totalFloors = $totalFloors->plus($f);
        }

        return [$floors, $amountInSteps->minus($totalFloors)];
    }
}
