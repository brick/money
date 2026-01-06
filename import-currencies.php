<?php

/**
 * This script creates an array of currencies from a trusted source.
 * The result of the script is exported in PHP format to be used by the library at runtime.
 *
 * This script is meant to be run by project maintainers, on a regular basis.
 */

declare(strict_types=1);

use Brick\Money\CurrencyType;
use Brick\VarExporter\VarExporter;

require __DIR__ . '/vendor/autoload.php';

/**
 * Country names are present in the ISO 4217 currency file, but not country codes.
 * This list maps country names to ISO 3166 country codes. It must be up to date or this script will error.
 * If country names present in the ISO currency file do not map to an actual country as defined by ISO 3166,
 * for example "EUROPEAN UNION", the corresponding value must be NULL.
 */
$countryCodes = [
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
    'ARAB MONETARY FUND' => null,
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
    'ESWATINI' => 'SZ',
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
    'NORTH MACEDONIA' => 'MK',
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
    'TÜRKİYE' => 'TR',
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

/**
 * List of additional historical names of countries.
 * Countries have ceased to exist or changed their official names.
 */
$historicalCountryCodes = [
    'BOLIVIA' => 'BQ',
    'BURMA' => 'BU',
    'CZECHOSLOVAKIA' => 'CS',
    'FRENCH SOUTHERN TERRITORIES' => 'FQ',
    'GERMAN DEMOCRATIC REPUBLIC' => 'DD',
    'HOLY SEE (VATICAN CITY STATE)' => 'VA',
    'LAO' => 'LA',
    'MOLDOVA, REPUBLIC OF' => 'MD',
    'NETHERLANDS' => 'NL',
    'NETHERLANDS ANTILLES' => 'AN',
    'RUSSIAN FEDERATION' => 'RU',
    'SAINT MARTIN' => 'MF',
    'SAINT-BARTHÉLEMY' => 'BL',
    'SERBIA AND MONTENEGRO' => 'CS',
    'SOUTHERN RHODESIA' => 'RH',
    'SUDAN' => 'SD',
    'SWAZILAND' => 'SZ',
    'TURKEY' => 'TR',
    'UNION OF SOVIET SOCIALIST REPUBLICS' => 'SU',
    'UNITED STATES' => 'US',
    'VENEZUELA' => 'VE',
    'VIETNAM' => 'VN',
    'YEMEN, DEMOCRATIC' => 'YD',
    'YUGOSLAVIA' => 'YU',
    'ZAIRE' => 'ZR',
];

/**
 * Minor units of historical currencies are not listed within list-three file. They have to be reconstructed manually from additional sources.
 * This list has to be up to date, otherwise import will fail. Use null for undefined minor units.
 *
 * @var array<string, int|null> $historicalMinorUnits
 */
$historicalMinorUnits = [
    'ADP' => 0,
    'AFA' => 2,
    'ALK' => 2,
    'ANG' => 2,
    'AOK' => 2,
    'AON' => 0,
    'AOR' => 0,
    'ARA' => 2,
    'ARP' => 2,
    'ARY' => 2,
    'ATS' => 2,
    'AYM' => 2,
    'AZM' => 2,
    'BAD' => 2,
    'BEC' => 2,
    'BEF' => 2,
    'BEL' => 2,
    'BGJ' => 2,
    'BGK' => 2,
    'BGN' => 2,
    'BGL' => 2,
    'BOP' => 2,
    'BRB' => 2,
    'BRC' => 2,
    'BRE' => 2,
    'BRN' => 2,
    'BRR' => 2,
    'BUK' => 2,
    'BYB' => 2,
    'BYR' => 2,
    'CHC' => 2,
    'CSD' => 2,
    'CSJ' => 2,
    'CSK' => 2,
    'CUC' => 2,
    'CYP' => 3,
    'DDM' => 2,
    'DEM' => 2,
    'ECS' => 2,
    'ECV' => 2,
    'EEK' => 2,
    'ESA' => 2,
    'ESB' => 2,
    'ESP' => 2,
    'EUR' => 2,
    'FIM' => 2,
    'FRF' => 2,
    'GEK' => 0,
    'GHC' => 2,
    'GHP' => 2,
    'GNE' => 2,
    'GNS' => 2,
    'GQE' => 2,
    'GRD' => 2,
    'GWE' => 2,
    'GWP' => 2,
    'HRD' => 2,
    'HRK' => 2,
    'IDR' => 2,
    'IEP' => 2,
    'ILP' => 3,
    'ILR' => 2,
    'ISJ' => 2,
    'ITL' => 2,
    'LAJ' => 2,
    'LSM' => 2,
    'LTL' => 2,
    'LTT' => 2,
    'LUC' => 2,
    'LUF' => 2,
    'LUL' => 2,
    'LVL' => 2,
    'LVR' => 2,
    'MGF' => 2,
    'MLF' => 2,
    'MRO' => 2,
    'MTL' => 3,
    'MTP' => 2,
    'MVQ' => 2,
    'MWK' => 2,
    'MXP' => 2,
    'MZE' => 2,
    'MZM' => 2,
    'NIC' => 2,
    'NLG' => 2,
    'PEH' => 2,
    'PEI' => 2,
    'PEN' => 2,
    'PES' => 2,
    'PLZ' => 2,
    'PTE' => 2,
    'RHD' => 2,
    'ROK' => 2,
    'ROL' => 2,
    'RON' => 2,
    'RUR' => 2,
    'SDD' => 2,
    'SDG' => 2,
    'SDP' => 2,
    'SIT' => 2,
    'SKK' => 2,
    'SLL' => 2,
    'SRG' => 2,
    'STD' => 2,
    'SUR' => 2,
    'SZL' => 2,
    'TJR' => 2,
    'TMM' => 2,
    'TPE' => 2,
    'TRL' => 2,
    'TRY' => 2,
    'UAK' => 2,
    'UGS' => 2,
    'UGW' => 2,
    'USS' => 2,
    'UYN' => 2,
    'UYP' => 2,
    'VEB' => 2,
    'VEF' => 2,
    'VNC' => 2,
    'XEU' => null,
    'XFO' => null,
    'XFU' => null,
    'XRE' => null,
    'YDD' => 2,
    'YUD' => 2,
    'YUM' => 2,
    'YUN' => 2,
    'ZAL' => 2,
    'ZMK' => 2,
    'ZRN' => 2,
    'ZRZ' => 2,
    'ZWC' => 2,
    'ZWD' => 2,
    'ZWL' => 2,
    'ZWN' => 2,
    'ZWR' => 2,
];

$currentCurrencyDatafile = file_get_contents('https://www.six-group.com/dam/download/financial-information/data-center/iso-currrency/lists/list-one.xml');
$historicalCurrencyDatafile = file_get_contents('https://www.six-group.com/dam/download/financial-information/data-center/iso-currrency/lists/list-three.xml');

$currencies = [];
$numericToCurrency = [];
$countryToCurrencyCurrent = [];
$countryToCurrencyHistorical = [];
$countryNamesFound = [];

$processCurrentDatafile = function ($file) use ($countryCodes, $historicalCountryCodes, &$currencies, &$numericToCurrency, &$countryNamesFound, &$countryToCurrencyCurrent): void {
    $countryToCurrencyCurrent = [];

    $document = new DOMDocument();
    $success = $document->loadXML($file);

    if (! $success) {
        echo "Failed to load XML file.\n";
        exit(1);
    }

    $countries = $document->getElementsByTagName('CcyNtry');
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
            throw new RuntimeException('No country code found for ' . $countryName);
        }

        $countryCode = $countryCodes[$countryName];

        if ($countryCode !== null) {
            if (! $isFund) {
                $countryToCurrencyCurrent[$countryCode][] = $currencyCode;
            }
        }

        $currencyName = checkCurrencyName($currencyName);
        $currencyCode = checkCurrencyCode($currencyCode);
        $numericCode = checkNumericCode($numericCode);
        $minorUnits = checkMinorUnits($minorUnits);

        $value = [$currencyCode, $numericCode, $currencyName, $minorUnits, CurrencyType::IsoCurrent];

        if (isset($currencies[$currencyCode])) {
            if ($currencies[$currencyCode] !== $value) {
                throw new RuntimeException('Inconsistent values found for currency code ' . $currencyCode);
            }
        } else {
            $currencies[$currencyCode] = $value;
            $numericToCurrency[$numericCode] = $currencyCode;
        }
    }

    foreach ($countryCodes as $countryName => $countryCode) {
        if (! isset($countryNamesFound[$countryName])) {
            echo 'Warning: ' . $countryName . ' not found in current ISO file.', PHP_EOL;
        }
    }
};

$processHistoricDatafile = function ($file) use ($countryCodes, $historicalCountryCodes, $historicalMinorUnits, &$currencies, &$numericToCurrency, &$countryNamesFound, &$countryToCurrencyHistorical, &$countryToCurrencyCurrent): void {
    $document = new DOMDocument();
    $success = $document->loadXML($file);

    if (! $success) {
        echo "Failed to load XML file.\n";
        exit(1);
    }

    $countries = $document->getElementsByTagName('HstrcCcyNtry');

    foreach ($countries as $country) {
        /** @var DOMElement $country */
        $countryName = getDomElementString($country, 'CtryNm');
        $currencyName = getDomElementString($country, 'CcyNm');
        $currencyCode = getDomElementString($country, 'Ccy');
        $numericCode = getDomElementString($country, 'CcyNbr');

        if (! array_key_exists($currencyCode, $historicalMinorUnits)) {
            throw new RuntimeException('Missing minor unit definition for currency ' . $currencyCode);
        }
        $minorUnits = $historicalMinorUnits[$currencyCode];

        $countryNamesFound[$countryName] = true;

        $isFund = $country->getElementsByTagName('CcyNm')->item(0)->getAttribute('IsFund') === 'true';

        if ($currencyName === null || $currencyCode === null && $numericCode === null && $minorUnits == null) {
            continue;
        }

        if ($minorUnits === 'N.A.' || $minorUnits === null) {
            continue;
        }

        // Historical data are messy
        $countryName = str_replace([' ', '  '], ['', ' '], $countryName);

        if (! array_key_exists($countryName, $countryCodes) && ! array_key_exists($countryName, $historicalCountryCodes)) {
            throw new RuntimeException('No country code found for ' . $countryName);
        }

        $countryCode = $countryCodes[$countryName] ?? $historicalCountryCodes[$countryName];

        if ($countryCode !== null) {
            if (! $isFund) {
                // Renaming a currency will cause it to be marked as withdrawn, while retaining its ISO code. Skip these records if the currency code is still in use in that country.
                // Note: If there is a change of country, keep the record.
                if (isset($countryToCurrencyCurrent[$countryCode]) && in_array($currencyCode, $countryToCurrencyCurrent[$countryCode], true)) {
                    continue;
                }

                if (! in_array($currencyCode, $countryToCurrencyHistorical[$countryCode] ?? [], true)) {
                    $countryToCurrencyHistorical[$countryCode][] = $currencyCode;
                }
            }
        }

        $currencyName = checkCurrencyName($currencyName);
        $currencyCode = checkCurrencyCode($currencyCode);
        $numericCode = checkNumericCode($numericCode);
        $minorUnits = checkMinorUnits($minorUnits);

        $value = [$currencyCode, $numericCode, $currencyName, $minorUnits, CurrencyType::IsoHistorical];

        if (! isset($currencies[$currencyCode])) {
            $currencies[$currencyCode] = $value;
        }
        if (! isset($numericToCurrency[$numericCode])) {
            $numericToCurrency[$numericCode] = $currencyCode;
        }
    }

    foreach ($countryCodes as $countryName => $countryCode) {
        if (! isset($countryNamesFound[$countryName])) {
            echo 'Warning: ' . $countryName . ' not found in current ISO file.', PHP_EOL;
        }
    }
};

$processCurrentDatafile($currentCurrencyDatafile);
$processHistoricDatafile($historicalCurrencyDatafile);

ksort($currencies);
ksort($numericToCurrency);
ksort($countryToCurrencyCurrent);
ksort($countryToCurrencyHistorical);

exportToFile(__DIR__ . '/data/iso-currencies.php', $currencies);
exportToFile(__DIR__ . '/data/numeric-to-currency.php', $numericToCurrency);
exportToFile(__DIR__ . '/data/country-to-currency.php', $countryToCurrencyCurrent);
exportToFile(__DIR__ . '/data/country-to-currency-historical.php', $countryToCurrencyHistorical);

$currentCurrenciesCount = count(array_filter($currencies, fn ($currency) => $currency[4] === CurrencyType::IsoCurrent));
$historicalCurrenciesCount = count(array_filter($currencies, fn ($currency) => $currency[4] === CurrencyType::IsoHistorical));

printf(
    'Exported %d current currencies and %d historical currencies in %d existing countries and %d historic countries.' . PHP_EOL,
    $currentCurrenciesCount,
    $historicalCurrenciesCount,
    count($countryToCurrencyCurrent),
    count($countryToCurrencyHistorical),
);

function exportToFile(string $file, array $data): void
{
    $data = '<?php ' . VarExporter::export($data, VarExporter::ADD_RETURN | VarExporter::INLINE_LITERAL_LIST | VarExporter::TRAILING_COMMA_IN_ARRAY);

    if (file_get_contents($file) === $data) {
        printf("%s: no change\n", $file);
    } else {
        file_put_contents($file, $data);
        printf("%s: UPDATED\n", $file);
    }
}

function getDomElementString(DOMElement $element, string $name): ?string
{
    foreach ($element->getElementsByTagName($name) as $child) {
        /** @var DOMElement $child */
        return $child->textContent;
    }

    return null;
}

/**
 * @throws RuntimeException
 */
function checkCurrencyName(string $name): string
{
    if ($name === '' || ! mb_check_encoding($name, 'UTF-8')) {
        throw new RuntimeException('Invalid currency name: ' . $name);
    }

    return $name;
}

/**
 * @throws RuntimeException
 */
function checkCurrencyCode(string $currencyCode): string
{
    if (preg_match('/^[A-Z]{3}$/', $currencyCode) !== 1) {
        throw new RuntimeException('Invalid currency code: ' . $currencyCode);
    }

    return $currencyCode;
}

/**
 * @throws RuntimeException
 */
function checkNumericCode(string $numericCode): int
{
    if (preg_match('/^[0-9]{3}$/', $numericCode) !== 1) {
        throw new RuntimeException('Invalid numeric code: ' . $numericCode);
    }

    return (int) ltrim($numericCode, '0');
}

/**
 * @throws RuntimeException
 */
function checkMinorUnits(string|int $minorUnits): int
{
    if (is_int($minorUnits)) {
        return $minorUnits;
    }

    if (preg_match('/^[0-9]{1}$/', $minorUnits) !== 1) {
        throw new RuntimeException('Invalid minor unit: ' . $minorUnits);
    }

    return (int) $minorUnits;
}
