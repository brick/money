<?php

declare(strict_types=1);

namespace Brick\Money\Tests\ExchangeRateProvider;

use Brick\Money\Currency;
use Brick\Money\Exception\InvalidArgumentException;
use Brick\Money\ExchangeRateProvider;
use Brick\Money\ExchangeRateProvider\ConfigurableProvider;
use Brick\Money\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for class ConfigurableProvider.
 */
class ConfigurableProviderTest extends AbstractTestCase
{
    /**
     * @param string $sourceCurrencyCode The code of the source currency.
     * @param string $targetCurrencyCode The code of the target currency.
     * @param string $expectedRate       The expected exchange rate, rounded DOWN to 3 decimals.
     */
    #[DataProvider('providerGetExchangeRate')]
    public function testGetExchangeRate(string $sourceCurrencyCode, string $targetCurrencyCode, ?string $expectedRate): void
    {
        $sourceCurrency = Currency::of($sourceCurrencyCode);
        $targetCurrency = Currency::of($targetCurrencyCode);

        $actualRate = $this->getExchangeRateProvider()->getExchangeRate($sourceCurrency, $targetCurrency);

        if ($expectedRate === null) {
            self::assertNull($actualRate);
        } else {
            self::assertNotNull($actualRate);
            self::assertSame($expectedRate, $actualRate->toBigDecimal()->toString());
        }
    }

    public static function providerGetExchangeRate(): array
    {
        return [
            ['USD', 'EUR', '0.8'],
            ['USD', 'GBP', '0.6'],
            ['USD', 'CAD', '1.2'],
            ['USD', 'BSD', '1'],
            ['EUR', 'USD', null],
        ];
    }

    public function testSameCurrencyReturnsOne(): void
    {
        self::assertBigNumberEquals('1', $this->getExchangeRateProvider()->getExchangeRate(
            Currency::of('EUR'),
            Currency::of('EUR'),
        ));
    }

    public function testSameCurrencyReturnsOneWithDimensions(): void
    {
        self::assertBigNumberEquals('1', $this->getExchangeRateProvider()->getExchangeRate(
            Currency::of('EUR'),
            Currency::of('EUR'),
            ['date' => '2026-03-12'],
        ));
    }

    #[DataProvider('providerInvalidExchangeRate')]
    public function testBuilderRejectsNonPositiveExchangeRate(string $exchangeRate): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Exchange rate must be greater than zero.');

        ConfigurableProvider::builder()
            ->addExchangeRate('USD', 'EUR', $exchangeRate);
    }

    public static function providerInvalidExchangeRate(): array
    {
        return [
            ['0'],
            ['-0.1'],
        ];
    }

    public function testBuilderRejectsSameCurrencyRateNotOne(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Same-currency conversion requires an exchange rate of 1.');

        ConfigurableProvider::builder()
            ->addExchangeRate('USD', 'USD', '1.01');
    }

    public function testBuilderRejectsDuplicateExchangeRate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Exchange rate already set for USD to EUR.');

        ConfigurableProvider::builder()
            ->addExchangeRate('USD', 'EUR', '0.8')
            ->addExchangeRate('USD', 'EUR', '0.9');
    }

    public function testBuilderAcceptsSameCurrencyRateOne(): void
    {
        $provider = ConfigurableProvider::builder()
            ->addExchangeRate('USD', 'USD', '1')
            ->build();

        self::assertBigNumberEquals('1', $provider->getExchangeRate(Currency::of('USD'), Currency::of('USD')));
        self::assertBigNumberEquals('1', $provider->getExchangeRate(Currency::of('EUR'), Currency::of('EUR')));
    }

    private function getExchangeRateProvider(): ExchangeRateProvider
    {
        return ConfigurableProvider::builder()
            ->addExchangeRate('USD', 'EUR', '0.8')
            ->addExchangeRate('USD', 'GBP', '0.6')
            ->addExchangeRate('USD', 'CAD', '1.2')
            ->addExchangeRate('USD', 'BSD', 1)
            ->build();
    }
}
