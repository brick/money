<?php

declare(strict_types=1);

namespace Brick\Money\Tests\Context;

use Brick\Math\BigNumber;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\RoundingMode;
use Brick\Money\Context\DefaultContext;
use Brick\Money\Currency;
use Brick\Money\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for class DefaultContext.
 */
class DefaultContextTest extends AbstractTestCase
{
    #[DataProvider('providerApplyTo')]
    public function testApplyTo(string $amount, string $currency, RoundingMode $roundingMode, string $expected): void
    {
        $amount = BigNumber::of($amount);
        $currency = Currency::of($currency);

        $context = new DefaultContext();

        if (self::isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $actual = $context->applyTo($amount, $currency, $roundingMode);

        if (! self::isExceptionClass($expected)) {
            self::assertBigDecimalIs($expected, $actual);
        }
    }

    public static function providerApplyTo(): array
    {
        return [
            ['1', 'USD', RoundingMode::Unnecessary, '1.00'],
            ['1.001', 'USD', RoundingMode::Unnecessary, RoundingNecessaryException::class],
            ['1.001', 'USD', RoundingMode::Down, '1.00'],
            ['1.001', 'USD', RoundingMode::Up, '1.01'],
            ['1', 'JPY', RoundingMode::Unnecessary, '1'],
            ['1.00', 'JPY', RoundingMode::Unnecessary, '1'],
            ['1.01', 'JPY', RoundingMode::Unnecessary, RoundingNecessaryException::class],
            ['1.01', 'JPY', RoundingMode::Down, '1'],
            ['1.01', 'JPY', RoundingMode::Up, '2'],
        ];
    }

    public function testGetStep(): void
    {
        $context = new DefaultContext();
        self::assertSame(1, $context->getStep());
    }
}
