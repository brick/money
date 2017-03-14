<?php

namespace Brick\Money\Tests\MoneyRounding;

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Brick\Math\RoundingMode;
use Brick\Money\MoneyRounding\CashRounding;
use Brick\Money\Tests\AbstractTestCase;

/**
 * Unit tests for class CashRounding.
 */
class CashRoundingTest extends AbstractTestCase
{
    /**
     * @dataProvider providerRound
     *
     * @param string $number
     * @param int    $step
     * @param int    $roundingMode
     * @param string $expectedResult
     */
    public function testRound($number, $scale, $step, $roundingMode, $expectedResult)
    {
        $cashRounding = new CashRounding($step, $roundingMode);
        $result = $cashRounding->round(BigNumber::of($number), $scale);

        $this->assertSame($expectedResult, (string) $result);
    }

    /**
     * @return array
     */
    public function providerRound()
    {
        return [
            ['3.37', 2, 2, RoundingMode::DOWN, '3.36'],
            ['3.37', 2, 2, RoundingMode::UP, '3.38'],
            ['3.37', 2, 5, RoundingMode::DOWN, '3.35'],
            ['3.37', 2, 5, RoundingMode::UP, '3.40'],
            ['3.37', 2, 10, RoundingMode::DOWN, '3.30'],
            ['3.37', 2, 10, RoundingMode::UP, '3.40'],
            ['3.37', 2, 20, RoundingMode::DOWN, '3.20'],
            ['3.37', 2, 20, RoundingMode::UP, '3.40'],
            ['3.37', 2, 50, RoundingMode::DOWN, '3.00'],
            ['3.37', 2, 50, RoundingMode::UP, '3.50'],
            ['3.37', 2, 100, RoundingMode::DOWN, '3.00'],
            ['3.37', 2, 100, RoundingMode::UP, '4.00'],
            ['1/7', 3, 2, RoundingMode::DOWN, '0.142'],
            ['1/7', 3, 2, RoundingMode::UP, '0.144'],
            ['1/7', 3, 5, RoundingMode::DOWN, '0.140'],
            ['1/7', 3, 5, RoundingMode::UP, '0.145'],
            ['1/7', 3, 10, RoundingMode::DOWN, '0.140'],
            ['1/7', 3, 10, RoundingMode::UP, '0.150'],
            ['1/7', 3, 20, RoundingMode::DOWN, '0.140'],
            ['1/7', 3, 20, RoundingMode::UP, '0.160'],
            ['1/7', 3, 50, RoundingMode::DOWN, '0.100'],
            ['1/7', 3, 50, RoundingMode::UP, '0.150'],
            ['1/7', 3, 100, RoundingMode::DOWN, '0.100'],
            ['1/7', 3, 100, RoundingMode::UP, '0.200'],
        ];
    }
}
