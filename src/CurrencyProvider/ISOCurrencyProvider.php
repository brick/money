<?php

namespace Brick\Money\CurrencyProvider;

use Brick\Money\Currency;
use Brick\Money\CurrencyProvider;
use Brick\Money\Exception\UnknownCurrencyException;

/**
 * Built-in provider for ISO currencies.
 */
class ISOCurrencyProvider implements CurrencyProvider
{
    /**
     * The raw currency data, indexed by currency code.
     *
     * @var array
     */
    private $currencyData;

    /**
     * The Currency instances.
     *
     * The instances are created on-demand, as soon as they are requested.
     *
     * @var Currency[]
     */
    private $currencies = [];

    /**
     * Whether the provider is in a partial state.
     *
     * This is true as long as all the currencies have not been instantiated yet.
     *
     * @var bool
     */
    private $isPartial = true;

    /**
     * Private constructor. Use `getInstance()` to obtain the singleton instance.
     */
    private function __construct()
    {
        $this->currencyData = require __DIR__ . '/../../data/iso-currencies.php';
    }

    /**
     * Returns the singleton instance of ISOCurrencyProvider.
     *
     * @return ISOCurrencyProvider
     */
    public static function getInstance()
    {
        static $instance;

        if ($instance === null) {
            $instance = new ISOCurrencyProvider();
        }

        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrency($currencyCode)
    {
        if (isset($this->currencies[$currencyCode])) {
            return $this->currencies[$currencyCode];
        }

        if (! isset($this->currencyData[$currencyCode])) {
            throw UnknownCurrencyException::unknownCurrency($currencyCode);
        }

        $currency = Currency::create(... $this->currencyData[$currencyCode]);

        return $this->currencies[$currencyCode] = $currency;
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableCurrencies()
    {
        if ($this->isPartial) {
            foreach ($this->currencyData as $currencyCode => $data) {
                if (! isset($this->currencies[$currencyCode])) {
                    $this->currencies[$currencyCode] = Currency::create(... $data);
                }
            }

            $this->isPartial = false;
        }

        return $this->currencies;
    }
}
