<?php

namespace Brick\Money\Tests\Context;

use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\RoundingMode;
use Brick\Money\Context\CashContext;
use Brick\Money\Context\AutoContext;
use Brick\Money\Currency;
use Brick\Money\Tests\AbstractTestCase;

use Brick\Math\BigNumber;

/**
 * Tests for class AutoContext.
 */
class AutoContextTest extends AbstractTestCase
{
    /**
     * @dataProvider providerApplyTo
     *
     * @param string $amount
     * @param string $currency
     * @param int    $roundingMode
     * @param string $expected
     */
    public function testApplyTo($amount, $currency, $roundingMode, $expected)
    {
        $amount = BigNumber::of($amount);
        $currency = Currency::of($currency);

        $context = new AutoContext();

        if ($this->isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $actual = $context->applyTo($amount, $currency, $roundingMode);

        if (! $this->isExceptionClass($expected)) {
            $this->assertBigDecimalIs($expected, $actual);
        }
    }

    /**
     * @return array
     */
    public function providerApplyTo()
    {
        return [
            ['1', 'USD', RoundingMode::UNNECESSARY, '1'],
            ['1.23', 'JPY', RoundingMode::UNNECESSARY, '1.23'],
            ['123/5000', 'EUR', RoundingMode::UNNECESSARY, '0.0246'],
            ['5/7', 'EUR', RoundingMode::UNNECESSARY, RoundingNecessaryException::class],
            ['5/7', 'EUR', RoundingMode::DOWN, \InvalidArgumentException::class]
        ];
    }

    public function testGetStep()
    {
        $context = new CashContext(5);
        $this->assertSame(5, $context->getStep());
    }
}
