# Brick\Money

<img src="https://raw.githubusercontent.com/brick/brick/master/logo.png" alt="" align="left" height="64">

A money and currency library for PHP.

[![Build Status](https://github.com/brick/money/workflows/CI/badge.svg)](https://github.com/brick/money/actions)
[![Coverage](https://codecov.io/github/brick/money/graph/badge.svg)](https://codecov.io/github/brick/money)
[![Latest Stable Version](https://poser.pugx.org/brick/money/v/stable)](https://packagist.org/packages/brick/money)
[![Total Downloads](https://poser.pugx.org/brick/money/downloads)](https://packagist.org/packages/brick/money)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](http://opensource.org/licenses/MIT)

## Introduction

This library provides immutable classes to work with monies and currencies, with exact arithmetic and explicit control over rounding — avoiding the silent rounding errors inherent to floating-point.

It is based on [brick/math](https://github.com/brick/math) and handles exact calculations on monies of any size.

### Installation

This library is installable via [Composer](https://getcomposer.org/):

```bash
composer require brick/money
```

### Requirements

This library requires PHP 8.2 or later.

For PHP 8.1 compatibility, you can use version `0.10`. For PHP 8.0, you can use version `0.8`. For PHP 7.4, you can use version `0.7`. For PHP 7.1, 7.2 & 7.3, you can use version `0.5`. Note that [these PHP versions are EOL](http://php.net/supported-versions.php) and not supported anymore. If you're still using one of these PHP versions, you should consider upgrading as soon as possible.

Although not required, it is recommended that you **install the [GMP](http://php.net/manual/en/book.gmp.php) or [BCMath](http://php.net/manual/en/book.bc.php) extension** to speed up calculations.

### Project status & release process

While this library is still under development, it is well tested and should be stable enough to use in production environments.

The current releases are numbered `0.x.y`. When a non-breaking change is introduced (adding new methods, optimizing existing code, etc.), `y` is incremented.

**When a breaking change is introduced, a new `0.x` version cycle is always started.**

It is therefore safe to lock your project to a given release cycle, such as `0.13.*`.

If you need to upgrade to a newer release cycle, check the [release history](https://github.com/brick/money/releases) for a list of changes introduced by each further `0.x.0` version.

#### Currency updates

This library is based on the latest ISO 4217 standard. This is a living standard, so updates to currencies are expected to happen regularly.

Updates to the following features **will be considered breaking changes**, and **will only be released in a new major version** after 1.0:

- Currencies obtained by alpha currency code such as `EUR` or `USD`, through `Currency::of()`, `Money::of()`, `Money::ofMinor()`, `IsoCurrencyProvider::getCurrency()`, etc.

The following features are evolving constantly, they will **not** be considered breaking changes and **may be updated in *minor* releases** after 1.0:

- Currencies obtained by numeric currency code such as `978` or `840`, through `Currency::ofNumericCode()`, `IsoCurrencyProvider::getCurrencyByNumericCode()`, etc.
- Current currencies obtained by country code such as `FR` or `US`, through `Currency::ofCountry()`, `IsoCurrencyProvider::getCurrencyForCountry()`, `IsoCurrencyProvider::getCurrenciesForCountry()`, etc.
- Historical currencies obtained by country code through `IsoCurrencyProvider::getHistoricalCurrenciesForCountry()`.

## Creating a Money

### From a regular currency value

To create a Money, call the `of()` factory method:

```php
use Brick\Money\Money;

$money = Money::of(50, 'USD'); // USD 50.00
$money = Money::of('19.9', 'USD'); // USD 19.90
```

If the given amount does not fit in the currency's default number of decimal places (2 for `USD`), you can pass a `RoundingMode`:

```php
$money = Money::of('123.456', 'USD'); // RoundingNecessaryException
$money = Money::of('123.456', 'USD', roundingMode: RoundingMode::Up); // USD 123.46
```

**Note that the rounding mode is only used once**, for the value provided in `of()`; it is not stored in the `Money` object, and any subsequent operation will still need to be passed a `RoundingMode` when necessary.

### From minor units (cents)

Alternatively, you can create a Money from a number of "minor units" (cents), using the `ofMinor()` method:

```php
use Brick\Money\Money;

$money = Money::ofMinor(1234, 'USD'); // USD 12.34
```

## Basic operations

Money is an immutable class: its value never changes, so it can be safely passed around. All operations on a Money therefore return a new instance:

```php
use Brick\Money\Money;

$money = Money::of(50, 'USD');

echo $money->plus('4.99'); // USD 54.99
echo $money->minus(1); // USD 49.00
echo $money->multipliedBy('1.999'); // USD 99.95
echo $money->dividedBy(4); // USD 12.50
```

You can add and subtract Money instances as well:

```php
use Brick\Money\Money;

$cost = Money::of(25, 'USD');
$shipping = Money::of('4.99', 'USD');
$discount = Money::of('2.50', 'USD');

echo $cost->plus($shipping)->minus($discount); // USD 27.49
```

If the two Money instances are not of the same currency, an exception is thrown:

```php
use Brick\Money\Money;

$a = Money::of(1, 'USD');
$b = Money::of(1, 'EUR');

$a->plus($b); // CurrencyMismatchException
```

If the result needs rounding, a [rounding mode](https://github.com/brick/math/blob/0.17.0/src/RoundingMode.php) must be passed as second parameter, or an exception is thrown:

```php
use Brick\Money\Money;
use Brick\Math\RoundingMode;

$money = Money::of(50, 'USD');

$money->plus('0.999'); // RoundingNecessaryException
$money->plus('0.999', RoundingMode::Down); // USD 50.99

$money->minus('0.999'); // RoundingNecessaryException
$money->minus('0.999', RoundingMode::Up); // USD 49.01

$money->multipliedBy('1.2345'); // RoundingNecessaryException
$money->multipliedBy('1.2345', RoundingMode::Down); // USD 61.72

$money->dividedBy(3); // RoundingNecessaryException
$money->dividedBy(3, RoundingMode::Up); // USD 16.67
```

## Comparing monies

You can compare two `Money` instances using the following methods:

- `compareTo()` (returns `-1|0|1`)
- `isEqualTo()`
- `isGreaterThan()`
- `isGreaterThanOrEqualTo()`
- `isLessThan()`
- `isLessThanOrEqualTo()`

These methods accept either a number or a `Money` instance. If the argument is a `Money` instance, it must be of the same currency as the `Money` instance on which the method is called, or an exception is thrown.

If you need to compare amount & currency without throwing on currency mismatch, you can use `isSameValueAs()` instead of `isEqualTo()`:

```php
$oneEuro = Money::of(1, 'EUR');

$oneEuro->isEqualTo(Money::of(1, 'EUR')); // true
$oneEuro->isEqualTo(Money::of(2, 'EUR')); // false
$oneEuro->isEqualTo(Money::of(1, 'USD')); // CurrencyMismatchException

$oneEuro->isSameValueAs(Money::of(1, 'EUR')); // true
$oneEuro->isSameValueAs(Money::of(2, 'EUR')); // false
$oneEuro->isSameValueAs(Money::of(1, 'USD')); // false
```

## Checking the sign

You can inspect the sign of a `Money` instance using the following methods:

- `getSign()` (returns `-1|0|1`)
- `isZero()`
- `isPositive()`
- `isPositiveOrZero()`
- `isNegative()`
- `isNegativeOrZero()`

## Money contexts

By default, monies have the official scale for the currency, as defined by the [ISO 4217 standard](https://www.currency-iso.org/) (for example, EUR and USD have 2 decimal places, while JPY has 0) and increment by steps of 1 minor unit (cent); they internally use what is called the `DefaultContext`. You can change this behaviour by providing a `Context` instance. All operations on Money return another Money with the same context. Each context targets a particular use case:

### Cash rounding

Some currencies do not allow the same increments for cash and cashless payments. For example, `CHF` (Swiss Franc) has 2 fraction digits and allows increments of 0.01 CHF, but Switzerland does not have coins of less than 5 cents, or 0.05 CHF.

You can deal with such monies using `CashContext`:

```php
use Brick\Money\Money;
use Brick\Money\Context\CashContext;
use Brick\Math\RoundingMode;

$money = Money::of(10, 'CHF', new CashContext(step: 5)); // CHF 10.00
$money->dividedBy(3, RoundingMode::Down); // CHF 3.30
$money->dividedBy(3, RoundingMode::Up); // CHF 3.35
```

### Custom scale

You can use custom scale monies by providing a `CustomContext`:

```php
use Brick\Money\Money;
use Brick\Money\Context\CustomContext;
use Brick\Math\RoundingMode;

$money = Money::of(10, 'USD', new CustomContext(scale: 4)); // USD 10.0000
$money->dividedBy(7, RoundingMode::Up); // USD 1.4286
```

### Auto scale

If you need monies that adjust their scale to fit the operation result, then `AutoContext` is for you:

```php
use Brick\Money\Money;
use Brick\Money\Context\AutoContext;

$money = Money::of('1.10', 'USD', new AutoContext()); // USD 1.1
$money->multipliedBy('2.5'); // USD 2.75
$money->dividedBy(8); // USD 0.1375
```

Note that it is not advised to use `AutoContext` to represent an intermediate calculation result: in particular, it cannot represent the result of all divisions, as some of them may lead to an infinite repeating decimal, which would throw an exception. For these use cases, `RationalMoney` is what you need. Head on to the next section!

## Advanced calculations

You may occasionally need to chain several operations on a Money, and only apply a rounding mode on the very last step; if you applied a rounding mode on every single operation, you might end up with a different result. This is where `RationalMoney` comes into play. This class internally stores the amount as a rational number (a fraction). You can create a `RationalMoney` from a `Money`, and conversely:

```php
use Brick\Money\Money;
use Brick\Math\RoundingMode;

$money = Money::of('9.5', 'EUR');                       // EUR 9.50 (Money)
$money = $money->toRational()                           // EUR 19/2 (RationalMoney)
  ->dividedBy(3)                                        // EUR 19/6 (RationalMoney)
  ->plus('17.795')                                      // EUR 12577/600 (RationalMoney)
  ->multipliedBy('1.196')                               // EUR 3760523/150000 (RationalMoney)
  ->toContext($money->getContext(), RoundingMode::Down) // EUR 25.07 (Money)
```

The intermediate results are represented as fractions, and no rounding is ever performed. The final `toContext()` method converts it to a `Money`, applying a context and a rounding mode. Most of the time you want the result in the same context as the original Money, which is what the example above does. But you can really apply any context:

```php
...
  ->toContext(new CustomContext(scale: 8), RoundingMode::Up); // EUR 25.07015334
```

## Money allocation

### Splitting

You can easily split a Money into a number of parts:

```php
use Brick\Money\Money;
use Brick\Money\SplitMode;

$money = Money::of(100, 'USD');
[$a, $b, $c] = $money->split(3, SplitMode::ToFirst); // USD 33.34, USD 33.33, USD 33.33
```

With `SplitMode::ToFirst`, the remainder is distributed one step at a time to the first parts. You can also use `SplitMode::Separate` to get the remainder as a separate last element:

```php
$money->split(3, SplitMode::Separate); // [USD 33.33, USD 33.33, USD 33.33, USD 0.01]
```

### Allocating

You can also allocate a Money according to a list of ratios. Say you want to distribute a profit of `987.65 CHF`f to 3 shareholders, having shares of `48%`, `41%` and `11%` of a company:

```php
use Brick\Money\Money;
use Brick\Money\AllocationMode;

$profit = Money::of('987.65', 'CHF');

// CHF 474.08, CHF 404.93, CHF 108.64
[$a, $b, $c] = $profit->allocate([48, 41, 11], AllocationMode::FloorToFirst);
```

It plays well with cash roundings, too:

```php
use Brick\Money\Money;
use Brick\Money\AllocationMode;
use Brick\Money\Context\CashContext;

$profit = Money::of('987.65', 'CHF', new CashContext(step: 5));

// CHF 474.10, CHF 404.95, CHF 108.60
[$a, $b, $c] = $profit->allocate([48, 41, 11], AllocationMode::FloorToFirst);
```

> [!TIP]
> Ratios can be any type of number (integer, decimal, rational) and do not need to add up to 100.

Several allocation modes are available. For example, given `1.00 USD` allocated by `[2, 3, 1]`:

| Mode                                                                                                                                         | Result                         |
|----------------------------------------------------------------------------------------------------------------------------------------------|--------------------------------|
| `FloorToFirst`<br><sub>Proportional floor amounts, remainder distributed to first allocatees (Martin Fowler method)</sub>                    | `0.34`, `0.50`, `0.16`         |
| `FloorToLargestRemainder`<br><sub>Proportional floor amounts, remainder distributed to largest fractional remainders (Hamilton method)</sub> | `0.33`, `0.50`, `0.17`         |
| `FloorToLargestRatio`<br><sub>Proportional floor amounts, remainder distributed to largest ratios</sub>                                      | `0.33`, `0.51`, `0.16`         |
| `FloorSeparate`<br><sub>Proportional floor amounts, remainder returned as a separate last element</sub>                                      | `0.33`, `0.50`, `0.16`, `0.01` |
| `BlockSeparate`<br><sub>Only complete blocks allocated, remainder returned as a separate last element</sub>                                  | `0.32`, `0.48`, `0.16`, `0.04` |

## Money bags (mixed currencies)

You may sometimes need to add monies in different currencies together. `MoneyBag` comes in handy for this:

```php
use Brick\Money\Money;
use Brick\Money\MoneyBag;

$eur = Money::of('12.34', 'EUR');
$jpy = Money::of(123, 'JPY');

$moneyBag = MoneyBag::of($eur, $jpy);

// or:

$moneyBag = MoneyBag::zero()->plus($eur)->plus($jpy);
```

You can add any kind of money to a MoneyBag: a `Money`, a `RationalMoney`, or even another `MoneyBag`.

What can you do with a MoneyBag? Well, you can convert it to a Money in the currency of your choice, using a `CurrencyConverter`. Keep reading!

## Currency conversion

This library ships with a `CurrencyConverter` that can convert any kind of money (`Money`, `RationalMoney` or `MoneyBag`) to a Money in another currency:

```php
use Brick\Money\CurrencyConverter;

$exchangeRateProvider = ...; // see below
$converter = new CurrencyConverter($exchangeRateProvider);

$money = Money::of('50', 'USD');
$converter->convert($money, 'EUR', roundingMode: RoundingMode::Down);
```

The converter performs the most precise calculation possible, internally representing the result as a rational number until the very last step.

To use the currency converter, you need an `ExchangeRateProvider`. Several implementations are provided, among which:

### ConfigurableProvider 

This provider allows you to configure exchange rates manually using a builder:

```php
use Brick\Money\ExchangeRateProvider\ConfigurableProvider;

$provider = ConfigurableProvider::builder()
    ->addExchangeRate('EUR', 'USD', '1.0987')
    ->addExchangeRate('USD', 'EUR', '0.9123')
    ->build();
```

### BaseCurrencyProvider

This provider builds on top of another exchange rate provider, for the quite common case where all your available exchange rates are relative to a single currency. For example, the [exchange rates](https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml) provided by the European Central Bank are all relative to EUR. You can use them directly to convert EUR to USD, but not USD to EUR, let alone USD to GBP.

This provider will combine exchange rates to get the expected result:

```php
use Brick\Money\ExchangeRateProvider\ConfigurableProvider;
use Brick\Money\ExchangeRateProvider\BaseCurrencyProvider;

$provider = ConfigurableProvider::builder()
    ->addExchangeRate('EUR', 'USD', '1.1')
    ->addExchangeRate('EUR', 'GBP', '0.9')
    ->build();

$provider = new BaseCurrencyProvider($provider, 'EUR');
$provider->getExchangeRate(Currency::of('EUR'), Currency::of('USD')); // 1.1
$provider->getExchangeRate(Currency::of('USD'), Currency::of('EUR')); // 10/11
$provider->getExchangeRate(Currency::of('GBP'), Currency::of('USD')); // 11/9
```

> [!TIP]
> Notice that exchange rate providers can return rational numbers (fractions)!

### PdoProvider

This provider reads exchange rates from a database table:

```php
use Brick\Money\ExchangeRateProvider\PdoProvider;

$pdo = new \PDO(...);

$provider = PdoProvider::builder($pdo, 'exchange_rates', 'exchange_rate')
    ->setSourceCurrencyColumn('source_currency_code')
    ->setTargetCurrencyColumn('target_currency_code')
    ->build();
```

`PdoProvider` supports fixed source or target currency, numeric currency codes, dimensions, and static `WHERE` conditions. Check the [PdoProviderBuilder](https://github.com/brick/money/blob/0.13.0/src/ExchangeRateProvider/Pdo/PdoProviderBuilder.php) class for more information.

#### Dimensions

Dimensions allow you to narrow exchange rate lookups beyond just a currency pair. For example, if your exchange rates table includes a date or rate type, you can bind these as dimensions:

```php
use Brick\Money\ExchangeRateProvider\PdoProvider;
use Brick\Money\ExchangeRateProvider\Pdo\SqlCondition;

$provider = PdoProvider::builder($pdo, 'exchange_rates', 'exchange_rate')
    ->setSourceCurrencyColumn('source_currency_code')
    ->setTargetCurrencyColumn('target_currency_code')
    ->bindDimension('year', fn (int $year) => new SqlCondition('year = ?', $year))
    ->bindDimension('month', fn (int $month) => new SqlCondition('month = ?', $month))
    ->build();
```

Each dimension binding is a callback that receives the dimension value and returns a `SqlCondition` (an SQL fragment with positional parameters), or `null` to skip filtering on that dimension.

You can then pass dimensions when looking up an exchange rate:

```php
use Brick\Money\Currency;

$rate = $provider->getExchangeRate(
    Currency::of('EUR'),
    Currency::of('USD'),
    ['year' => 2017, 'month' => 8],
);
```

Dimensions also flow through the `CurrencyConverter`:

```php
use Brick\Math\RoundingMode;
use Brick\Money\CurrencyConverter;
use Brick\Money\Money;

$converter = new CurrencyConverter($provider);

$converter->convert(
    Money::of('10.00', 'EUR'),
    'USD',
    ['year' => 2017, 'month' => 8],
    roundingMode: RoundingMode::HalfUp,
);
```

A dimension binding can also accept complex types. For example, you can accept a `DateTimeInterface` and derive multiple SQL conditions from it:

```php
$provider = PdoProvider::builder($pdo, 'exchange_rates', 'exchange_rate')
    ->setSourceCurrencyColumn('source_currency_code')
    ->setTargetCurrencyColumn('target_currency_code')
    ->bindDimension(
        'as_of',
        fn (\DateTimeInterface $date) => new SqlCondition(
            'year = ? AND month = ?',
            (int) $date->format('Y'),
            (int) $date->format('m'),
        ),
    )
    ->build();

$rate = $provider->getExchangeRate(
    Currency::of('EUR'),
    Currency::of('USD'),
    ['as_of' => new \DateTimeImmutable('2017-08-15')],
);
```

If your table may contain multiple matching rows (e.g. daily rates within a month), use `orderBy()` to select the first match:

```php
$provider = PdoProvider::builder($pdo, 'exchange_rates', 'exchange_rate')
    ->setSourceCurrencyColumn('source_currency_code')
    ->setTargetCurrencyColumn('target_currency_code')
    ->bindDimension('date', fn (string $date) => new SqlCondition('date <= ?', $date))
    ->orderBy('date', 'DESC')
    ->build();
```

> [!NOTE]
> If a dimension is passed that has no binding, the provider returns `null` (rate not found). Conversely, if a bound dimension is **not** passed, its condition is simply omitted from the query — this may cause multiple rows to match and throw an exception unless `orderBy()` is configured.

### CachedProvider

This provider wraps another provider and caches the results using a [PSR-16](https://www.php-fig.org/psr/psr-16/) cache. Both found and not-found rates are cached:

```php
use Brick\Money\ExchangeRateProvider\CachedProvider;

$cachedProvider = new CachedProvider($provider);
```

By default, an in-memory array cache is used. You can pass your own PSR-16 cache implementation and a TTL:

```php
$cachedProvider = new CachedProvider(
    provider: $provider,
    cache: $yourPsr16Cache,
    ttl: 3600, // seconds
);
```

Dimensions are included in the cache key. Scalars, `null`, `DateTimeInterface`, and `Stringable` values are supported out of the box. For other object types, pass a custom normalizer:

```php
$cachedProvider = new CachedProvider(
    provider: $provider,
    cache: $yourPsr16Cache,
    dimensionObjectNormalizer: function (object $value) {
        if ($value instanceof YourCustomType) {
            return $value->toKey(); // string, int, float, bool accepted
        }

        return null; // fall back to built-in handling
    },
);
```

If a dimension value is not cacheable (unsupported object type and no custom normalizer), the cache is bypassed and the wrapped provider is queried directly.

### ChainProvider

This provider tries multiple providers in order and returns the first non-null result:

```php
use Brick\Money\ExchangeRateProvider\ChainProvider;

$provider = new ChainProvider($primaryProvider, $fallbackProvider);
```

This is useful for combining providers — for example, different providers each supporting specific currency pairs or dimensions. Dimensions are passed through to each provider unchanged.

### Write your own provider

Writing your own provider is easy: the `ExchangeRateProvider` interface has just one method, `getExchangeRate()`, that takes the currency codes, optional dimensions, and returns a number or `null` if the rate is not found:

```php
public function getExchangeRate(
    Currency $sourceCurrency,
    Currency $targetCurrency,
    array $dimensions = [],
): ?BigNumber;
```

## Custom currencies

Money supports ISO 4217 currencies by default. You can also use custom currencies by creating a `Currency` instance. Let's create a Bitcoin currency:

```php
use Brick\Money\Currency;
use Brick\Money\Money;

$bitcoin = new Currency(
    'XBT',     // currency code
    null,      // numeric currency code, optional; set to null if unused
    'Bitcoin', // currency name
    8,         // default scale
);
```

You can now use this Currency instead of a currency code:

```php
$money = Money::of('0.123', $bitcoin); // XBT 0.12300000
```

> [!WARNING]
> Do not create multiple `Currency` instances with the same currency code but different data. The library identifies currencies by code, so conflicting instances used together may lead to undefined behaviour.

## Formatting

**Formatting requires the [intl extension](http://php.net/manual/en/book.intl.php).**

Money objects can be formatted according to a given locale:

```php
$money = Money::of(5000, 'USD');
echo $money->formatToLocale('en_US'); // $5,000.00
echo $money->formatToLocale('fr_FR'); // 5 000,00 $US
```

Alternatively, you can format Money objects with your own instance of [NumberFormatter](http://php.net/manual/en/class.numberformatter.php), which gives you room for customization:

```php
use Brick\Money\Money;
use Brick\Money\Formatter\MoneyNumberFormatter;

$formatter = new \NumberFormatter('en_US', \NumberFormatter::CURRENCY);
$formatter->setSymbol(\NumberFormatter::CURRENCY_SYMBOL, 'US$');
$formatter->setSymbol(\NumberFormatter::MONETARY_GROUPING_SEPARATOR_SYMBOL, '·');
$formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, 2);

$money = Money::of(5000, 'USD');
echo (new MoneyNumberFormatter($formatter))->format($money); // US$5·000.00
```

> [!IMPORTANT]
> Because formatting is performed using `NumberFormatter`, the amount is converted to floating point in the process; so discrepancies can appear when formatting very large monetary values.

## Storing Money objects in a database

### Persisting the amount

- **As an integer**: in many applications, monies are only ever used with their default scale (e.g. 2 decimal places for `USD`, 0 for `JPY`). In this case, the best practice is to store minor units (cents) as an integer field:
  
  ```php
  $integerAmount = $money->getMinorAmount()->toInt();
  ```
  
  And later retrieve it as:
  
  ```php
  Money::ofMinor($integerAmount, $currencyCode);
  ```
  
  This approach works well with all currencies, without having to worry about the scale. You only have to worry about not overflowing an integer (which would throw an exception), but this is unlikely to happen unless you're dealing with huge amounts of money.
  
- **As a decimal**: for most other cases, storing the amount string as a decimal type is advised:
  
  ```php
  $decimalAmount = $money->getAmount()->toString();
  ```
  
  And later retrieve it as:
  
  ```php
  Money::of($decimalAmount, $currencyCode);
  ```

### Persisting the currency

- **As a string**: if you only deal with ISO currencies, or custom currencies having a 3-letter currency code, you can store the currency in a `CHAR(3)`. Otherwise, you'll most likely need a `VARCHAR`. You may also use an `ENUM` if your application uses a fixed list of currencies.
  
  ```php
  $currencyCode = $money->getCurrency()->getCurrencyCode();
  ```
  
  When retrieving the currency: you can use ISO currency codes directly in `Money::of()` and `Money::ofMinor()`. For custom currencies, you'll need to convert them to `Currency` instances first.
  
- **As an integer**: if you only deal with ISO currencies, or custom currencies with a numeric code, you may store the currency code as an integer:
  
  ```php
  $numericCode = $money->getCurrency()->getNumericCode();
  ```

  When retrieving the currency: for ISO currencies, first convert the numeric code to a `Currency` instance with `Currency::ofNumericCode()`, then pass that `Currency` instance to `Money::of()` or `Money::ofMinor()`. For custom currencies, you'll likewise need to convert the numeric code to a `Currency` instance first.

- **Hardcoded**: if your application only ever deals with one currency, you may very well hardcode the currency code and not store it in your database at all.

> [!NOTE]
> Numeric currency codes of ISO currencies may be reassigned over time, so prefer alphabetical currency codes whenever possible.

### Using an ORM

If you're using an ORM such as Doctrine, it is advised to store the amount and currency separately, and perform conversion in the getters/setters:

  ```php
  class Entity
  {
      private int $price;
      private string $currencyCode;

      public function getPrice() : Money
      {
          return Money::ofMinor($this->price, $this->currencyCode);
      }

      public function setPrice(Money $price) : void
      {
          $this->price = $price->getMinorAmount()->toInt();
          $this->currencyCode = $price->getCurrency()->getCurrencyCode();
      }
  }
  ```

## FAQ

> How does this project compare with [moneyphp/money](https://github.com/moneyphp/money)?

Please see [this discussion](https://github.com/brick/money/issues/28).

## PHPStan extension

A third-party [PHPStan extension](https://github.com/simPod/phpstan-brick-money) is available for this library. It provides more specific throw type narrowing for brick/money methods, so that PHPStan can infer the exact exception classes thrown. Note that this extension is not maintained by the author of brick/money.
