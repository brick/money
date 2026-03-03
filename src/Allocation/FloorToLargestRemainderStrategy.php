<?php

declare(strict_types=1);

namespace Brick\Money\Allocation;

use Brick\Math\BigInteger;

use function array_keys;
use function array_map;
use function usort;

/**
 * @internal
 */
final class FloorToLargestRemainderStrategy extends AbstractFloorAbsorbStrategy
{
    /**
     * @param list<BigInteger> $ratios
     *
     * @return list<int>
     *
     * @pure
     */
    protected function priorityIndices(BigInteger $amountInSteps, array $ratios): array
    {
        $total = BigInteger::sum(...$ratios);
        $fractions = array_map(
            fn (BigInteger $ratio) => $amountInSteps->multipliedBy($ratio)->remainder($total),
            $ratios,
        );

        $indices = array_keys($ratios);

        usort($indices, fn (int $a, int $b): int => $fractions[$b]->compareTo($fractions[$a]) ?: $a <=> $b);

        return $indices;
    }
}
