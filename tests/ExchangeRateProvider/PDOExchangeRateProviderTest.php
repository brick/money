<?php

namespace Brick\Money\Tests\ExchangeRateProvider;

use Brick\Money\ExchangeRateProvider\PDOProvider;
use Brick\Money\ExchangeRateProvider\PDOProviderConfiguration;
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
     * @return PDOProvider
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

        $configuration = new PDOProviderConfiguration();

        $configuration->tableName                = 'exchange_rate';
        $configuration->sourceCurrencyColumnName = 'source_currency';
        $configuration->targetCurrencyColumnName = 'target_currency';
        $configuration->exchangeRateColumnName   = 'exchange_rate';

        return new PDOProvider($pdo, $configuration);
    }

    /**
     * @dataProvider providerGetExchangeRate
     *
     * @param string       $sourceCurrencyCode The code of the source currency.
     * @param string       $targetCurrencyCode The code of the target currency.
     * @param float|string $expectedResult     The expected exchange rate, or an exception class if expected.
     */
    public function testGetExchangeRate($sourceCurrencyCode, $targetCurrencyCode, $expectedResult)
    {
        if ($this->isExceptionClass($expectedResult)) {
            $this->expectException($expectedResult);
        }

        $actualRate = $this->getPDOExchangeRateProvider()->getExchangeRate($sourceCurrencyCode, $targetCurrencyCode);

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
