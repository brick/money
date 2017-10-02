<?php

/**
 * This script creates an array of currencies from a trusted source.
 * The result of the script is exported in PHP format to be used by the library at runtime.
 *
 * This script is meant to be run by project maintainers, on a regular basis.
 */

/**
 * Country names are present in the ISO 4217 currency file, but not country codes.
 * This list maps country names to ISO 3166 country codes. It must be up to date or this script will error.
 * If country names present in the ISO currency file do not map to an actual country as defined by ISO 3166,
 * for example "EUROPEAN UNION", the corresponding value must be NULL.
 */
$countryCodes= [
    'AFGHANISTAN' => 'AF',
    'ÅLAND ISLANDS' => 'AX',
    'ALBANIA' => 'AL',
    'ALGERIA' => 'DZ',
    'AMERICAN SAMOA' => 'AS',
    'ANDORRA' => 'AD',
    'ANGOLA' => 'AO',
    'ANGUILLA' => 'AI',
    'ANTARCTICA' => 'AQ',
    'ANTIGUA AND BARBUDA' => 'AG',
    'ARGENTINA' => 'AR',
    'ARMENIA' => 'AM',
    'ARUBA' => 'AW',
    'AUSTRALIA' => 'AU',
    'AUSTRIA' => 'AT',
    'AZERBAIJAN' => 'AZ',
    'BAHAMAS (THE)' => 'BS',
    'BAHRAIN' => 'BH',
    'BANGLADESH' => 'BD',
    'BARBADOS' => 'BB',
    'BELARUS' => 'BY',
    'BELGIUM' => 'BE',
    'BELIZE' => 'BZ',
    'BENIN' => 'BJ',
    'BERMUDA' => 'BM',
    'BHUTAN' => 'BT',
    'BOLIVIA (PLURINATIONAL STATE OF)' => 'BO',
    'BONAIRE, SINT EUSTATIUS AND SABA' => 'BQ',
    'BOSNIA AND HERZEGOVINA' => 'BA',
    'BOTSWANA' => 'BW',
    'BOUVET ISLAND' => 'BV',
    'BRAZIL' => 'BR',
    'BRITISH INDIAN OCEAN TERRITORY (THE)' => 'IO',
    'BRUNEI DARUSSALAM' => 'BN',
    'BULGARIA' => 'BG',
    'BURKINA FASO' => 'BF',
    'BURUNDI' => 'BI',
    'CABO VERDE' => 'CV',
    'CAMBODIA' => 'KH',
    'CAMEROON' => 'CM',
    'CANADA' => 'CA',
    'CAYMAN ISLANDS (THE)' => 'KY',
    'CENTRAL AFRICAN REPUBLIC (THE)' => 'CF',
    'CHAD' => 'TD',
    'CHILE' => 'CL',
    'CHINA' => 'CN',
    'CHRISTMAS ISLAND' => 'CX',
    'COCOS (KEELING) ISLANDS (THE)' => 'CC',
    'COLOMBIA' => 'CO',
    'COMOROS (THE)' => 'KM',
    'CONGO (THE DEMOCRATIC REPUBLIC OF THE)' => 'CD',
    'CONGO (THE)' => 'CG',
    'COOK ISLANDS (THE)' => 'CK',
    'COSTA RICA' => 'CR',
    'CÔTE D\'IVOIRE' => 'CI',
    'CROATIA' => 'HR',
    'CUBA' => 'CU',
    'CURAÇAO' => 'CW',
    'CYPRUS' => 'CY',
    'CZECHIA' => 'CZ',
    'DENMARK' => 'DK',
    'DJIBOUTI' => 'DJ',
    'DOMINICA' => 'DM',
    'DOMINICAN REPUBLIC (THE)' => 'DO',
    'ECUADOR' => 'EC',
    'EGYPT' => 'EG',
    'EL SALVADOR' => 'SV',
    'EQUATORIAL GUINEA' => 'GQ',
    'ERITREA' => 'ER',
    'ESTONIA' => 'EE',
    'ETHIOPIA' => 'ET',
    'EUROPEAN UNION' => null,
    'FALKLAND ISLANDS (THE) [MALVINAS]' => 'FK',
    'FAROE ISLANDS (THE)' => 'FO',
    'FIJI' => 'FJ',
    'FINLAND' => 'FI',
    'FRANCE' => 'FR',
    'FRENCH GUIANA' => 'GF',
    'FRENCH POLYNESIA' => 'PF',
    'FRENCH SOUTHERN TERRITORIES (THE)' => 'TF',
    'GABON' => 'GA',
    'GAMBIA (THE)' => 'GM',
    'GEORGIA' => 'GE',
    'GERMANY' => 'DE',
    'GHANA' => 'GH',
    'GIBRALTAR' => 'GI',
    'GREECE' => 'GR',
    'GREENLAND' => 'GL',
    'GRENADA' => 'GD',
    'GUADELOUPE' => 'GP',
    'GUAM' => 'GU',
    'GUATEMALA' => 'GT',
    'GUERNSEY' => 'GG',
    'GUINEA' => 'GN',
    'GUINEA-BISSAU' => 'GW',
    'GUYANA' => 'GY',
    'HAITI' => 'HT',
    'HEARD ISLAND AND McDONALD ISLANDS' => 'HM',
    'HOLY SEE (THE)' => 'VA',
    'HONDURAS' => 'HN',
    'HONG KONG' => 'HK',
    'HUNGARY' => 'HU',
    'ICELAND' => 'IS',
    'INDIA' => 'IN',
    'INDONESIA' => 'ID',
    'IRAN (ISLAMIC REPUBLIC OF)' => 'IR',
    'IRAQ' => 'IQ',
    'IRELAND' => 'IE',
    'ISLE OF MAN' => 'IM',
    'ISRAEL' => 'IL',
    'ITALY' => 'IT',
    'JAMAICA' => 'JM',
    'JAPAN' => 'JP',
    'JERSEY' => 'JE',
    'JORDAN' => 'JO',
    'KAZAKHSTAN' => 'KZ',
    'KENYA' => 'KE',
    'KIRIBATI' => 'KI',
    'KOREA (THE DEMOCRATIC PEOPLE’S REPUBLIC OF)' => 'KP',
    'KOREA (THE REPUBLIC OF)' => 'KR',
    'KUWAIT' => 'KW',
    'KYRGYZSTAN' => 'KG',
    'LAO PEOPLE’S DEMOCRATIC REPUBLIC (THE)' => 'LA',
    'LATVIA' => 'LV',
    'LEBANON' => 'LB',
    'LESOTHO' => 'LS',
    'LIBERIA' => 'LR',
    'LIBYA' => 'LY',
    'LIECHTENSTEIN' => 'LI',
    'LITHUANIA' => 'LT',
    'LUXEMBOURG' => 'LU',
    'MACAO' => 'MO',
    'MACEDONIA (THE FORMER YUGOSLAV REPUBLIC OF)' => 'MK',
    'MADAGASCAR' => 'MG',
    'MALAWI' => 'MW',
    'MALAYSIA' => 'MY',
    'MALDIVES' => 'MV',
    'MALI' => 'ML',
    'MALTA' => 'MT',
    'MARSHALL ISLANDS (THE)' => 'MH',
    'MARTINIQUE' => 'MQ',
    'MAURITANIA' => 'MR',
    'MAURITIUS' => 'MU',
    'MAYOTTE' => 'YT',
    'MEXICO' => 'MX',
    'MICRONESIA (FEDERATED STATES OF)' => 'FM',
    'MOLDOVA (THE REPUBLIC OF)' => 'MD',
    'MONACO' => 'MC',
    'MONGOLIA' => 'MN',
    'MONTENEGRO' => 'ME',
    'MONTSERRAT' => 'MS',
    'MOROCCO' => 'MA',
    'MOZAMBIQUE' => 'MZ',
    'MYANMAR' => 'MM',
    'NAMIBIA' => 'NA',
    'NAURU' => 'NR',
    'NEPAL' => 'NP',
    'NETHERLANDS (THE)' => 'NL',
    'NEW CALEDONIA' => 'NC',
    'NEW ZEALAND' => 'NZ',
    'NICARAGUA' => 'NI',
    'NIGER (THE)' => 'NE',
    'NIGERIA' => 'NG',
    'NIUE' => 'NU',
    'NORFOLK ISLAND' => 'NF',
    'NORTHERN MARIANA ISLANDS (THE)' => 'MP',
    'NORWAY' => 'NO',
    'OMAN' => 'OM',
    'PAKISTAN' => 'PK',
    'PALAU' => 'PW',
    'PALESTINE, STATE OF' => 'PS',
    'PANAMA' => 'PA',
    'PAPUA NEW GUINEA' => 'PG',
    'PARAGUAY' => 'PY',
    'PERU' => 'PE',
    'PHILIPPINES (THE)' => 'PH',
    'PITCAIRN' => 'PN',
    'POLAND' => 'PL',
    'PORTUGAL' => 'PT',
    'PUERTO RICO' => 'PR',
    'QATAR' => 'QA',
    'RÉUNION' => 'RE',
    'ROMANIA' => 'RO',
    'RUSSIAN FEDERATION (THE)' => 'RU',
    'RWANDA' => 'RW',
    'SAINT BARTHÉLEMY' => 'BL',
    'SAINT HELENA, ASCENSION AND TRISTAN DA CUNHA' => 'SH',
    'SAINT KITTS AND NEVIS' => 'KN',
    'SAINT LUCIA' => 'LC',
    'SAINT MARTIN (FRENCH PART)' => 'MF',
    'SAINT PIERRE AND MIQUELON' => 'PM',
    'SAINT VINCENT AND THE GRENADINES' => 'VC',
    'SAMOA' => 'WS',
    'SAN MARINO' => 'SM',
    'SAO TOME AND PRINCIPE' => 'ST',
    'SAUDI ARABIA' => 'SA',
    'SENEGAL' => 'SN',
    'SERBIA' => 'RS',
    'SEYCHELLES' => 'SC',
    'SIERRA LEONE' => 'SL',
    'SINGAPORE' => 'SG',
    'SINT MAARTEN (DUTCH PART)' => 'SX',
    'SLOVAKIA' => 'SK',
    'SLOVENIA' => 'SI',
    'SOLOMON ISLANDS' => 'SB',
    'SOMALIA' => 'SO',
    'SOUTH AFRICA' => 'ZA',
    'SOUTH GEORGIA AND THE SOUTH SANDWICH ISLANDS' => 'GS',
    'SOUTH SUDAN' => 'SS',
    'SPAIN' => 'ES',
    'SRI LANKA' => 'LK',
    'SUDAN (THE)' => 'SD',
    'SURINAME' => 'SR',
    'SVALBARD AND JAN MAYEN' => 'SJ',
    'SWAZILAND' => 'SZ',
    'SWEDEN' => 'SE',
    'SWITZERLAND' => 'CH',
    'SYRIAN ARAB REPUBLIC' => 'SY',
    'TAIWAN (PROVINCE OF CHINA)' => 'TW',
    'TAJIKISTAN' => 'TJ',
    'TANZANIA, UNITED REPUBLIC OF' => 'TZ',
    'THAILAND' => 'TH',
    'TIMOR-LESTE' => 'TL',
    'TOGO' => 'TG',
    'TOKELAU' => 'TK',
    'TONGA' => 'TO',
    'TRINIDAD AND TOBAGO' => 'TT',
    'TUNISIA' => 'TN',
    'TURKEY' => 'TR',
    'TURKMENISTAN' => 'TM',
    'TURKS AND CAICOS ISLANDS (THE)' => 'TC',
    'TUVALU' => 'TV',
    'UGANDA' => 'UG',
    'UKRAINE' => 'UA',
    'UNITED ARAB EMIRATES (THE)' => 'AE',
    'UNITED KINGDOM OF GREAT BRITAIN AND NORTHERN IRELAND (THE)' => 'GB',
    'UNITED STATES MINOR OUTLYING ISLANDS (THE)' => 'UM',
    'UNITED STATES OF AMERICA (THE)' => 'US',
    'URUGUAY' => 'UY',
    'UZBEKISTAN' => 'UZ',
    'VANUATU' => 'VU',
    'VENEZUELA (BOLIVARIAN REPUBLIC OF)' => 'VE',
    'VIET NAM' => 'VN',
    'VIRGIN ISLANDS (BRITISH)' => 'VG',
    'VIRGIN ISLANDS (U.S.)' => 'VI',
    'WALLIS AND FUTUNA' => 'WF',
    'WESTERN SAHARA' => 'EH',
    'YEMEN' => 'YE',
    'ZAMBIA' => 'ZM',
    'ZIMBABWE' => 'ZW',
];

$data = file_get_contents('https://www.currency-iso.org/dam/downloads/lists/list_one.xml');

$document = new DOMDocument();
$document->loadXML($data);

$countries = $document->getElementsByTagName('CcyNtry');
$currencies = [];

$numericToCurrency = [];
$countryToCurrency = [];
$countryNamesFound = [];

foreach ($countries as $country) {
    /** @var DOMElement $country */
    $countryName = getDomElementString($country, 'CtryNm');
    $currencyName = getDomElementString($country, 'CcyNm');
    $currencyCode = getDomElementString($country, 'Ccy');
    $numericCode = getDomElementString($country, 'CcyNbr');
    $minorUnits = getDomElementString($country, 'CcyMnrUnts');

    $countryNamesFound[$countryName] = true;

    $isFund = $country->getElementsByTagName('CcyNm')->item(0)->getAttribute('IsFund') === 'true';

    if ($currencyName === null || $currencyCode === null && $numericCode === null && $minorUnits == null) {
        continue;
    }

    if ($minorUnits === 'N.A.') {
        continue;
    }

    if (! array_key_exists($countryName, $countryCodes)) {
        throw new \RuntimeException('No country code found for ' . $countryName);
    }

    $countryCode = $countryCodes[$countryName];

    if ($countryCode !== null) {
        if (! $isFund) {
            $countryToCurrency[$countryCode][] = $currencyCode;
        }
    }

    $currencyName = checkCurrencyName($currencyName);
    $currencyCode = checkCurrencyCode($currencyCode);
    $numericCode = checkNumericCode($numericCode);
    $minorUnits = checkMinorUnits($minorUnits);

    $value = [$currencyCode, $numericCode, $currencyName, $minorUnits];

    if (isset($currencies[$currencyCode])) {
        if ($currencies[$currencyCode] !== $value) {
            throw new \RuntimeException('Inconsistent values found for currency code ' . $currencyCode);
        }
    } else {
        $currencies[$currencyCode] = $value;
        $numericToCurrency[$numericCode] = $currencyCode;
    }
}

foreach ($countryCodes as $countryName => $countryCode) {
    if (! isset($countryNamesFound[$countryName])) {
        echo 'Warning: ' . $countryName . ' not found in ISO file.', PHP_EOL;
    }
}

ksort($currencies);
ksort($numericToCurrency);
ksort($countryToCurrency);

exportToFile(__DIR__ . '/data/iso-currencies.php', $currencies);
exportToFile(__DIR__ . '/data/numeric-to-currency.php', $numericToCurrency);
exportToFile(__DIR__ . '/data/country-to-currency.php', $countryToCurrency);

printf('Exported %d currencies in %d countries.' . PHP_EOL, count($currencies), count($countryToCurrency));

/**
 * @param string $file
 * @param mixed  $data
 *
 * @return void
 */
function exportToFile($file, $data)
{
    file_put_contents($file, sprintf("<?php return %s;\n", export($data)));
}

/**
 * Compact & pretty alternative to var_export().
 *
 * @param mixed $variable
 *
 * @return string
 */
function export($variable)
{
    if (! is_array($variable)) {
        return var_export($variable, true);
    }

    if (array_values($variable) === $variable) {
        $values = [];

        foreach ($variable as $value) {
            $values[] = var_export($value, true);
        }

        return '[' . implode(', ', $values) . ']';
    }

    $result = "[\n";

    foreach ($variable as $key => $value) {
        $result .= '  ' . var_export($key, true) . ' => ' . export($value) . ",\n";
    }

    $result .= ']';

    return $result;
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
function checkCurrencyName($name)
{
    if ($name === '' || ! mb_check_encoding($name, 'UTF-8')) {
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
    if (preg_match('/^[A-Z]{3}$/', $currencyCode) !== 1) {
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
    if (preg_match('/^[0-9]{3}$/', $numericCode) !== 1) {
        throw new \RuntimeException('Invalid numeric code: ' . $numericCode);
    }

    return (int) ltrim($numericCode, '0');
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
    if (preg_match('/^[0-9]{1}$/', $minorUnits) !== 1) {
        throw new \RuntimeException('Invalid minor unit: ' . $minorUnits);
    }

    return (int) $minorUnits;
}
