<?php

declare(strict_types=1);

namespace Brick\Money\Allocation;

use Brick\Math\BigInteger;

use function array_map;

/**
 * @internal
 */
abstract readonly class AbstractFloorStrategy implements AllocationStrategy
{
    /**
     * Returns the floor step counts and the remainder for each ratio.
     *
     * @param non-empty-list<BigInteger> $ratios
     *
     * @return array{non-empty-list<BigInteger>, BigInteger} [floors, remainderSteps]
     *
     * @pure
     */
    final protected function computeFloors(BigInteger $amountInSteps, array $ratios): array
    {
        $total = BigInteger::sum(...$ratios);

        $floors = array_map(
            fn (BigInteger $ratio) => $amountInSteps->multipliedBy($ratio)->quotient($total),
            $ratios,
        );

        $remainderSteps = $amountInSteps->minus(BigInteger::sum(...$floors));

        return [$floors, $remainderSteps];
    }
}
