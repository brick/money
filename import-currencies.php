<?php

/**
 * This script creates an array of currencies from a trusted source.
 * The result of the script is exported in PHP format to be used by the library at runtime.
 *
 * This script is meant to be run by project maintainers, on a regular basis.
 */

$data = file_get_contents('https://www.currency-iso.org/dam/downloads/lists/list_one.xml');

$document = new DOMDocument();
$document->loadXML($data);

$countries = $document->getElementsByTagName('CcyNtry');
$result = [];

foreach ($countries as $country) {
    /** @var DOMElement $country */
    $name = getDomElementString($country, 'CcyNm');
    $currencyCode = getDomElementString($country, 'Ccy');
    $numericCode = getDomElementString($country, 'CcyNbr');
    $minorUnits = getDomElementString($country, 'CcyMnrUnts');

    if ($name === null || $currencyCode === null && $numericCode === null && $minorUnits == null) {
        continue;
    }

    if ($minorUnits == 'N.A.') {
        continue;
    }

    $name = checkName($name);
    $currencyCode = checkCurrencyCode($currencyCode);
    $numericCode = checkNumericCode($numericCode);
    $minorUnits = checkMinorUnits($minorUnits);

    $value = [$currencyCode, $numericCode, $name, $minorUnits];

    if (isset($result[$currencyCode])) {
        if ($result[$currencyCode] !== $value) {
            throw new \RuntimeException('Inconsistent values found for currency code ' . $currencyCode);
        }
    } else {
        $result[$currencyCode] = $value;
    }
}

exportToFile('data/iso-currencies.php', $result);

printf('Exported %d currencies.' . PHP_EOL, count($result));

/**
 * @param string $file
 * @param mixed  $data
 *
 * @return void
 */
function exportToFile($file, $data)
{
    file_put_contents($file, sprintf("<?php return %s;\n", var_export($data, true)));
}

/**
 * @param DOMElement $element
 * @param string     $name
 *
 * @return string|null
 */
function getDomElementString(DOMElement $element, $name)
{
    foreach ($element->getElementsByTagName($name) as $child) {
        /** @var $child DOMElement */
        return $child->textContent;
    }

    return null;
}

/**
 * @param string $name
 *
 * @return string
 *
 * @throws RuntimeException
 */
function checkName($name)
{
    if ($name == '' || ! mb_check_encoding($name, 'UTF-8')) {
        throw new \RuntimeException('Invalid currency name: ' . $name);
    }

    return $name;
}

/**
 * @param string $currencyCode
 *
 * @return string
 *
 * @throws RuntimeException
 */
function checkCurrencyCode($currencyCode)
{
    if (preg_match('/^[A-Z]{3}$/', $currencyCode) == 0) {
        throw new \RuntimeException('Invalid currency code: ' . $currencyCode);
    }

    return $currencyCode;
}

/**
 * @param string $numericCode
 *
 * @return int
 *
 * @throws RuntimeException
 */
function checkNumericCode($numericCode)
{
    if ($numericCode == 'Nil') {
        return 0;
    }

    if (preg_match('/^[0-9]{3}$/', $numericCode) == 0) {
        throw new \RuntimeException('Invalid numeric code: ' . $numericCode);
    }

    return $numericCode;
}

/**
 * @param string $minorUnits
 *
 * @return int
 *
 * @throws RuntimeException
 */
function checkMinorUnits($minorUnits)
{
    if (preg_match('/^[0-9]{1}$/', $minorUnits) == 0) {
        throw new \RuntimeException('Invalid minor unit: ' . $minorUnits);
    }

    return (int) $minorUnits;
}
