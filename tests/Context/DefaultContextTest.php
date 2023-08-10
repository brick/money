<?php

declare(strict_types=1);

namespace Brick\Money\Tests\Context;

use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\RoundingMode;
use Brick\Money\Context\DefaultContext;
use Brick\Money\Currency;
use Brick\Money\Tests\AbstractTestCase;

use Brick\Math\BigNumber;

/**
 * Tests for class DefaultContext.
 */
class DefaultContextTest extends AbstractTestCase
{
    /**
     * @dataProvider providerApplyTo
     */
    public function testApplyTo(string $amount, string $currency, RoundingMode $roundingMode, string $expected) : void
    {
        $amount = BigNumber::of($amount);
        $currency = Currency::of($currency);

        $context = new DefaultContext();

        if ($this->isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $actual = $context->applyTo($amount, $currency, $roundingMode);

        if (! $this->isExceptionClass($expected)) {
            $this->assertBigDecimalIs($expected, $actual);
        }
    }

    public function providerApplyTo() : array
    {
        return [
            ['1', 'USD', RoundingMode::UNNECESSARY, '1.00'],
            ['1.001', 'USD', RoundingMode::UNNECESSARY, RoundingNecessaryException::class],
            ['1.001', 'USD', RoundingMode::DOWN, '1.00'],
            ['1.001', 'USD', RoundingMode::UP, '1.01'],
            ['1', 'JPY', RoundingMode::UNNECESSARY, '1'],
            ['1.00', 'JPY', RoundingMode::UNNECESSARY, '1'],
            ['1.01', 'JPY', RoundingMode::UNNECESSARY, RoundingNecessaryException::class],
            ['1.01', 'JPY', RoundingMode::DOWN, '1'],
            ['1.01', 'JPY', RoundingMode::UP, '2']
        ];
    }

    public function testGetStep() : void
    {
        $context = new DefaultContext();
        self::assertSame(1, $context->getStep());
    }
}
