<?php

namespace Brick\Money\Tests\CurrencyConversion\ExchangeRateProvider;

use Brick\Money\Currency;
use Brick\Money\ExchangeRateProvider\PDOExchangeRateProvider;
use Brick\Money\ExchangeRateProvider\PDOExchangeRateProviderConfiguration;
use Brick\Money\Exception\CurrencyConversionException;
use Brick\Money\Tests\AbstractTestCase;

/**
 * Tests for class PDOExchangeRateProvider.
 */
class PDOExchangeRateProviderTest extends AbstractTestCase
{
    /**
     * Configures and returns a fresh PDOExchangeRateProvider instance.
     *
     * @return PDOExchangeRateProvider
     */
    private function getPDOExchangeRateProvider()
    {
        $pdo = new \PDO('sqlite::memory:');

        $pdo->query('
            CREATE TABLE exchange_rate (
                source_currency TEXT NOT NULL,
                target_currency TEXT NOT NULL,
                exchange_rate REAL NOT NULL
            )
        ');

        $statement = $pdo->prepare('INSERT INTO exchange_rate VALUES (?, ?, ?)');

        $statement->execute(['EUR', 'USD', 1.1]);
        $statement->execute(['USD', 'EUR', 0.9]);
        $statement->execute(['USD', 'CAD', 1.2]);

        $configuration = new PDOExchangeRateProviderConfiguration();

        $configuration->tableName                = 'exchange_rate';
        $configuration->sourceCurrencyColumnName = 'source_currency';
        $configuration->targetCurrencyColumnName = 'target_currency';
        $configuration->exchangeRateColumnName   = 'exchange_rate';

        return new PDOExchangeRateProvider($pdo, $configuration);
    }

    /**
     * @dataProvider providerGetExchangeRate
     *
     * @param string       $sourceCurrency The currency code of the source.
     * @param string       $targetCurrency The currency code of the target currency.
     * @param float|string $expectedResult The expected exchange rate, or an exception class if expected.
     */
    public function testGetExchangeRate($sourceCurrency, $targetCurrency, $expectedResult)
    {
        $sourceCurrency = Currency::of($sourceCurrency);
        $targetCurrency = Currency::of($targetCurrency);

        if ($this->isExceptionClass($expectedResult)) {
            $this->setExpectedException($expectedResult);
        }

        $actualRate = $this->getPDOExchangeRateProvider()->getExchangeRate($sourceCurrency, $targetCurrency);

        if (! $this->isExceptionClass($expectedResult)) {
            $this->assertEquals($expectedResult, $actualRate);
        }
    }

    /**
     * @return array
     */
    public function providerGetExchangeRate()
    {
        return [
            ['USD', 'EUR', 0.9],
            ['EUR', 'USD', 1.1],
            ['USD', 'CAD', 1.2],
            ['CAD', 'USD', CurrencyConversionException::class],
            ['EUR', 'CAD', CurrencyConversionException::class],
        ];
    }
}
