<?php

declare(strict_types=1);

namespace Brick\Money\Allocation;

use Brick\Math\BigInteger;

use function array_map;

/**
 * @internal
 */
final readonly class BlockSeparateStrategy implements AllocationStrategy
{
    public function allocate(BigInteger $amountInSteps, array $ratios): array
    {
        $total = BigInteger::sum(...$ratios);

        [$quotient, $remainderSteps] = $amountInSteps->quotientAndRemainder($total);

        $steps = array_map(fn (BigInteger $ratio) => $quotient->multipliedBy($ratio), $ratios);
        $steps[] = $remainderSteps;

        return $steps;
    }
}
