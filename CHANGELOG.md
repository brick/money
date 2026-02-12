# Changelog

## UNRELEASED (0.12.0)

💥 **Breaking changes**

- **Calling the following methods with floating-point values is no longer supported**, explicitly cast floats to string `(string) $float` to get the same behaviour as before (brick/math#105):
  - `Money::of()`, `ofMinor()`, `plus()`, `minus()`, `multipliedBy()`, `dividedBy()`, `quotient()`, `quotientAndRemainder()`, `convertedTo()`
  - `RationalMoney::of()`, `plus()`, `minus()`, `multipliedBy()`, `dividedBy()`
  - `AbstractMoney::compareTo()`, `isEqualTo()`, `isLessThan()`, `isLessThanOrEqualTo()`, `isGreaterThan()`, `isGreaterThanOrEqualTo()`
  - `ConfigurableProvider::setExchangeRate()`
- **Calling `Currency::of()` with a numeric code is no longer supported**, use `Currency::ofNumericCode()` instead (#104)
- **Calling the following methods with a numeric currency code is no longer supported**, use a `Currency` instance from `Currency::ofNumericCode()` instead (#104):
  - `Money::of()`
  - `Money::ofMinor()`
  - `Money::zero()`
  - `Money::convertedTo()`
  - `RationalMoney::of()`
  - `CurrencyConverter::convert()`
  - `CurrencyConverter::convertToRational()`
  - `ISOCurrencyProvider::getCurrency()`
- **`RationalMoney` is now always simplified to lowest terms:** `USD 25/100` is automatically simplified to `USD 1/4`
- `ISOCurrencyProvider` has been renamed to `IsoCurrencyProvider`

Deprecated methods removed:

- `Currency::is()` has been removed, use `Currency::isEqualTo()` instead
- `MoneyBag::getAmount()` has been removed, use `MoneyBag::getMoney()->getAmount()` instead
- `Money::formatTo()` has been removed, use `Money::formatToLocale()` instead
- `Money::formatWith()` has been removed, use `MoneyNumberFormatter::format()` instead

⚠️ **Deprecations**

- Method `RationalMoney::simplified()` is deprecated, as it is now a no-op
- Method `Money::getUnscaledAmount()` is deprecated, use `getAmount()->getUnscaledValue()` instead
- Method `Money::total()` is deprecated, use `sum()` instead

📌 **Compatibility**

- brick/money now requires `brick/math:~0.15`

✨ **New features**

- New method: `Money::sum()` (replaces `total()`)

## [0.11.1](https://github.com/brick/money/releases/tag/0.11.1) - 2026-02-12

⚠️ **Deprecations**

- Added explicit `trigger_deprecation()` calls to methods that were already marked as `@deprecated`, so deprecation notices are now emitted at runtime

📌 **Compatibility**

- Restricted compatibility to `brick/math:~0.14.4`

👌 **Improvements**

- Fixed calls to deprecated brick/math and brick/money APIs

## [0.11.0](https://github.com/brick/money/releases/tag/0.11.0) - 2026-01-22

💥 **Breaking changes**

- Minimum PHP version is now 8.2
- The following classes are now `final`:
  - `CurrencyConversionException`
  - `MoneyMismatchException`
  - `UnknownCurrencyException`
- `CustomContext` now validates the step and will throw an exception if an invalid step is given
- Interface `MoneyContainer` has been removed (replaced with `Monetary`)
- Method `AbstractMoney::getAmounts()` has been removed (replaced with `getMonies()`)
- Method `MoneyBag::getAmounts()` has been removed (replaced with `getMonies()`)
- `CurrencyConverter::convert()` and `convertToRational()` now accept a `Monetary` instance (which still includes `Money`, `RationalMoney` and `MoneyBag`)
- `MoneyBag::add()` and `subtract()` now accept a `Monetary` instance (which still includes `Money`, `RationalMoney` and `MoneyBag`)

⚠️ **Deprecations**

- Calling `Currency::of()` with a numeric code is deprecated, use `Currency::ofNumericCode()` instead
- Calling `ISOCurrencyProvider::getCurrency()` with a numeric code is deprecated, use `getCurrencyByNumericCode()` instead
- Calling `CurrencyConverter::convert()` or `convertToRational()` with a numeric currency code is deprecated, use a `Currency` instance instead
- Calling `Money::of()`, `ofMinor()`, `zero()` or `convertedTo()` with a numeric currency code is deprecated, use a `Currency` instance instead
- Calling `RationalMoney::of()` with a numeric currency code is deprecated, use a `Currency` instance instead
- `MoneyBag::getAmount()` is deprecated, use `getMoney()` instead
- `Money::formatTo()` is deprecated, use `Money::formatToLocale()` instead
- `Money::formatWith()` is deprecated, use `MoneyNumberFormatter::format()` instead
- `Currency::is()` is deprecated, use `Currency::isEqualTo()` instead

> [!IMPORTANT]
> The convenience of passing a currency by ISO numeric code in addition to alphabetic code has been deprecated, leaving only alphabetic-code lookup in generic APIs. For example, `Money::of()` will accept `Currency|string` in the future, instead of `Currency|string|int` today.
> This makes explicit the separation between retrieval by alphabetic code, which has strong backwards compatibility guarantees, and retrieval by numeric code, which may change in minor versions due to ISO reassignments.
> This will require users to explicitly obtain a currency through `Currency::ofNumericCode()`, which is documented as not being covered by the same BC guarantees.

✨ **New features**

- **Support for historical currencies** in `Money::of()`, `Currency::of()`, etc. (#104 by @survik1)
- New enum: `CurrencyType`
- New methods:
  - `Currency::getCurrencyType()` returns the type of the currency
  - `Currency::ofNumericCode()` returns a currency by its numeric ISO 4217 code
  - `Currency::isEqualTo()` compares two currencies for equality (replaces `is()`)
  - `ISOCurrencyProvider::getCurrencyByNumericCode()` returns a currency by its numeric code
  - `ISOCurrencyProvider::getHistoricalCurrenciesForCountry()` returns historical currencies for a country
  - `MoneyBag::getMoney()` returns the contained amount in a given currency (replaces `getAmount()`)
  - `MoneyBag::getMonies()` returns the contained monies (replaces `getAmounts()`)
  - `Money::formatToLocale()` formats the amount to a locale (replaces `formatTo()`) (#105 by @mklepaczewski)
  - `RationalMoney::zero()` returns a zero `RationalMoney` in a given currency
- New interfaces:
  - `Monetary` (replaces `MoneyContainer`)
  - `MoneyFormatter` formats a given `Money` object (#105 by @mklepaczewski)
- New classes:
  - `MoneyLocaleFormatter` formats a given `Money` object to a locale (#105 by @mklepaczewski)
  - `MoneyNumberFormatter` formats a given `Money` object using a `NumberFormatter` instance (#105 by @mklepaczewski)

👌 **Improvements**

- `MoneyException` now extends `RuntimeException` instead of `Exception`

📝 **Documentation**

- Backward compatibility promise notes for currency updates

## [0.10.3](https://github.com/brick/money/releases/tag/0.10.3) - 2025-09-03

👌 **Improvements**

- Compatibility with `brick/math` version `0.14` (#101 by @markwalet)

## [0.10.2](https://github.com/brick/money/releases/tag/0.10.2) - 2025-08-05

✨ **New features**

- Add possibility to pass previous exception in `CurrencyConversionException` (#99 by @arokettu)

## [0.10.1](https://github.com/brick/money/releases/tag/0.10.1) - 2025-03-05

👌 **Improvements**

- Compatibility with `brick/math` version `0.13` (#96 by @ekvedaras)

## [0.10.0](https://github.com/brick/money/releases/tag/0.10.0) - 2024-10-12

💥 **ISO currency changes**

- `ZWG` (Zimbabwe Gold) has been added
- `ZWL` (Zimbabwean Dollar) has been removed
- `SLL` (Sierra Leonean Leone) has been removed
- The currency of Zimbabwe (`ZW`) has been changed to `ZWG` (Zimbabwe Gold)
- The `SLL` currency has been removed from Sierra Leone (`SL`), which only has `SLE` now

## [0.9.0](https://github.com/brick/money/releases/tag/0.9.0) - 2023-11-26

💥 **Breaking changes**

- Minimum PHP version is now 8.1
- `PDOProviderConfiguration` no longer has getters, its properties are `public readonly`
- `RoundingMode` from `brick/math` is now an enum, so:
  - all methods accepting an `int` rounding mode now accept a `RoundingMode` instance instead
  - this should be transparent to your application, as you'll be using the same constants such as `RoundingMode::UP`

## [0.8.1](https://github.com/brick/money/releases/tag/0.8.1) - 2023-09-23

👌 **Improvement**

`Currency` now implements `JsonSerializable` ([#79](https://github.com/brick/money/pull/79)).

Thanks [@joelvh](https://github.com/joelvh)!

## [0.8.0](https://github.com/brick/money/releases/tag/0.8.0) - 2023-01-16

💥 **Breaking changes**

- Minimum PHP version is now 8.0
- Due to Croatia's adoption of the Euro on January 1st, 2023:
  - the `HRK` currency (Kuna) has been removed from the ISO currency provider
  - the `HR` country (Croatia) is now mapped to `EUR` (Euro)
- `PDOProviderConfiguration` now has a proper constructor, and its properties are no longer public
- `PDOProviderConfiguration` now throws exceptions in the constructor when configuration is invalid
- All documented union types are now strongly typed:
  - If you have a custom `ExchangeRateProvider` implementation, you will need to update your `getExchangeRate()` method signature
  - If you were passing `Stringable` objects to `of()` or any of the methods internally calling `of()`, and have `strict_types` enabled, you will need to explicitly cast these objects to `string` first

## [0.7.1](https://github.com/brick/money/releases/tag/0.7.1) - 2023-01-16

👌 **Improvements**

- Compatibility with `brick/math` version `0.11`

## [0.7.0](https://github.com/brick/money/releases/tag/0.7.0) - 2022-10-06

💥 **Breaking changes**

- JSON extension is now required for PHP 7.4 (always available with PHP >= 8.0)
- `AbstractMoney` is now officially sealed, extending it yourself is not supported

✨ **New features**

- `Money` and `RationalMoney` now implement `JsonSerializable`

## [0.6.0](https://github.com/brick/money/releases/tag/0.6.0) - 2022-08-02

💥 **Breaking changes**

- Minimum PHP version is now 7.4
- `AbstractMoney::getAmount()` now has a return type
- `CurrencyConverter`'s constructor does not accept a default `$context` anymore
- `CurrencyConverter::convert()` now requires the `$context` previously accepted by the constructor as third parameter
- `Money::allocateWithRemainder()` now refuses to allocate a portion of the amount that cannot be spread over all ratios, and instead adds that amount to the remainder (#55)
- `Money::splitWithRemainder()` now behaves like `allocateWithRemainder()`

✨ **New ISO currencies**

- `SLE` (Leone) in Sierra Leone (`SL`)

👌 **Improvements**

- Compatibility with `brick/math` version `0.10`

## [0.5.2](https://github.com/brick/money/releases/tag/0.5.2) - 2021-04-03

✨ **New methods**

- `Money::allocateWithRemainder()`
- `Money::splitWithRemainder()`

These methods perform like their `allocate()` and `split()` counterparts, but append the remainder at the end of the returned array instead of spreading it over the first monies.

Thanks @NCatalani!

## [0.5.1](https://github.com/brick/money/releases/tag/0.5.1) - 2021-02-10

👌 **Improvement**

`BaseCurrencyProvider` now always returns a `BigNumber` for convenience (#37).
This is useful if you're using `BaseCurrencyProvider` on its own, not just in `CurrencyConverter`.

Thanks @rdarcy1!

## [0.5.0](https://github.com/brick/money/releases/tag/0.5.0) - 2020-08-19

👌 **Improvements**

- compatibility with `brick/math` version `0.9`

⚠️ **Caution**

When using `brick/math` version `0.9`, the `Money` factory methods such as `of()` and `ofMinor()` now accept decimal numbers in the form `.123` and `123.`,  and do not throw an exception anymore in this case.

## [0.4.5](https://github.com/brick/money/releases/tag/0.4.5) - 2020-05-31

🐛 **Bug fix**

`MoneyBag::getAmount()`, `add()` and `subtract()` would throw an exception when using a custom currency (#25).

## [0.4.4](https://github.com/brick/money/releases/tag/0.4.4) - 2020-01-23

✨ **New method**

`AbstractMoney::isAmountAndCurrencyEqualTo()` compares a money to another. (#17)

This method is different from `isEqualTo()` in 2 aspects:

- it only accepts another money, not a raw number;
- **it returns `false` if the money is in another currency**, instead of throwing an exception.

## [0.4.3](https://github.com/brick/money/releases/tag/0.4.3) - 2020-01-09

🛠 **Improvements**

- `MoneyBag::getAmount()` now accepts an ISO numeric currency code as well

✨ **New methods**

- `CurrencyConverter::convertToRational()` converts to a `RationalMoney` (#22)

## [0.4.2](https://github.com/brick/money/releases/tag/0.4.2) - 2019-07-04

Performance improvement when calling `Money::formatTo()` many times for the same locale.

## [0.4.1](https://github.com/brick/money/releases/tag/0.4.1) - 2018-10-17

Added support for `brick/math` version `0.8`.

## [0.4.0](https://github.com/brick/money/releases/tag/0.4.0) - 2018-10-09

**Breaking Changes**

- Deprecated method `BigRational::toMoney()` has been removed, use `BigRational::to()` instead;
- `BigRational::__toString()` now always outputs the amount in non-simplified rational form.

**New methods**

- `BigRational::simplified()` returns a copy of the money with the amount simplified.

## [0.3.4](https://github.com/brick/money/releases/tag/0.3.4) - 2018-09-12

ISO currency list update.

## [0.3.3](https://github.com/brick/money/releases/tag/0.3.3) - 2018-08-22

ISO currency list update.

## [0.3.2](https://github.com/brick/money/releases/tag/0.3.2) - 2018-08-20

`Money::formatTo()` can now format the amount as a whole number:

```php
formatTo(string $locale, bool $allowWholeNumber = false) : string
```

By default, `formatTo()` always outputs all the fraction digits:

```php
Money::of('23.5', 'USD')->formatTo('en_US'); // $23.50
Money::of(23, 'USD')->formatTo('en_US'); // $23.00
```

But can now be allowed to return the whole number by passing `true` as a second argument:

```php
Money::of('23.5', 'USD')->formatTo('en_US', true); // $23.50
Money::of(23, 'USD')->formatTo('en_US', true); // $23
```

*Note that this version now requires `brick/math` version `0.7.3`. This is not a BC break. If you've locked your composer.json to an earlier version, you will just not be able to install `brick/money` version `0.3.2`.*

## [0.3.1](https://github.com/brick/money/releases/tag/0.3.1) - 2018-08-04

ISO currency list update.

## [0.3.0](https://github.com/brick/money/releases/tag/0.3.0) - 2018-07-26

**New methods:**

- `CurrencyConversionException::getSourceCurrencyCode()`
- `CurrencyConversionException::getTargetCurrencyCode()`

This allows to programmatically get the failing currency pair when an exchange rate is not available.

**Breaking change:**

- `CurrencyConversionException` constructor signature changed

Although this is technically a breaking change and requires a version bump, your code is unlikely to be affected, unless you're creating `CurrencyConversionException` instances manually (you shouldn't).

## [0.2.4](https://github.com/brick/money/releases/tag/0.2.4) - 2018-01-10

ISO currency list update.

## [0.2.3](https://github.com/brick/money/releases/tag/0.2.3) - 2017-12-01

Bug fix: `Money::allocate()` incorrectly allocated negative monies.

## [0.2.2](https://github.com/brick/money/releases/tag/0.2.2) - 2017-11-20

`Money::formatTo()` now always respects the scale of the Money.

## [0.2.1](https://github.com/brick/money/releases/tag/0.2.1) - 2017-11-05

New method: `CustomContext::getScale()`

## [0.2.0](https://github.com/brick/money/releases/tag/0.2.0) - 2017-10-02

- Minimum requirement is now PHP 7.1
- `BigRational::toMoney()` has been deprecated; use `to()` instead. This is the result of a factorization of a common feature in Money and RationalMoney.

## [0.1.1](https://github.com/brick/money/releases/tag/0.1.1) - 2017-12-08

Backports from 0.2.x:

- `CustomContext::getScale()`
- `Money::formatTo()` now always respects the scale of the Money
- Bug fix: `Money::allocate()` incorrectly allocated negative monies

## [0.1.0](https://github.com/brick/money/releases/tag/0.1.0) - 2017-10-02

First beta release!
