<?php

namespace Brick\Money\ExchangeRateProvider;

use Brick\Money\Currency;
use Brick\Money\ExchangeRateProvider;
use Brick\Money\Exception\CurrencyConversionException;

/**
 * Reads exchange rates from a PDO database connection.
 */
class PDOExchangeRateProvider implements ExchangeRateProvider
{
    /**
     * @var \PDOStatement
     */
    private $statement;

    /**
     * @param \PDO                                 $pdo
     * @param PDOExchangeRateProviderConfiguration $configuration
     */
    public function __construct(\PDO $pdo, PDOExchangeRateProviderConfiguration $configuration)
    {
        $this->statement = $pdo->prepare(sprintf(
            'SELECT %s FROM %s WHERE %s = ? AND %s = ?',
            $configuration->exchangeRateColumnName,
            $configuration->tableName,
            $configuration->sourceCurrencyColumnName,
            $configuration->targetCurrencyColumnName
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getExchangeRate(Currency $source, Currency $target)
    {
        $this->statement->execute([
            $source->getCode(),
            $target->getCode()
        ]);

        $exchangeRate = $this->statement->fetchColumn();

        if ($exchangeRate === false) {
            throw CurrencyConversionException::exchangeRateNotAvailable($source, $target);
        }

        return $exchangeRate;
    }
}
