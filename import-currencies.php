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
 * This list has to be exhaustive, otherwise import will fail. Use null when minor units are unknown.
 *
 * The data sources used for minor unit values in this list are archived copies of the following URLs:
 *
 * http://www.currency-iso.org/dam/downloads/dl_iso_table_a1.xml
 * https://www.currency-iso.org/dam/downloads/lists/list_one.xml
 * https://www.six-group.com/dam/download/financial-information/data-center/iso-currrency/amendments/lists/list_one.xml
 * https://www.six-group.com/dam/download/financial-information/data-center/iso-currrency/lists/list-one.xml
 * http://www.bsi-global.com/Technical+Information/Publications/_Publications/tig90x.doc
 *
 * The official ISO sources are currency-iso.org (old website) and six-group.com (new website).
 * The URL of the XML file changed several times over the years.
 *
 * The tig90x.doc file is an old file (the last archived copy is from 2004) from the British Standards Institution.
 * BSI is a member body of ISO, and SAP cites tig90x.doc as a reference in publicly available documentation, so we consider this list authoritative.
 *
 * See: https://github.com/brick/money/issues/109
 *
 * @var array<string, int|null> $historicalMinorUnits
 */
$historicalMinorUnits = [
    'ADP' => 0, // https://web.archive.org/web/20040702015452/http://www.bsi-global.com/Technical+Information/Publications/_Publications/tig90x.doc
    'AFA' => 2, // https://web.archive.org/web/20040702015452/http://www.bsi-global.com/Technical+Information/Publications/_Publications/tig90x.doc
    'ALK' => null,
    'ANG' => 2, // https://web.archive.org/web/20250324115248/https://www.six-group.com/dam/download/financial-information/data-center/iso-currrency/lists/list-one.xml
    'AOK' => null,
    'AON' => null,
    'AOR' => null,
    'ARA' => null,
    'ARP' => null,
    'ARY' => null,
    'ATS' => 2, // https://web.archive.org/web/20040702015452/http://www.bsi-global.com/Technical+Information/Publications/_Publications/tig90x.doc
    'AYM' => null,
    'AZM' => 2, // https://web.archive.org/web/20040702015452/http://www.bsi-global.com/Technical+Information/Publications/_Publications/tig90x.doc
    'BAD' => null,
    'BEC' => null,
    'BEF' => 0, // https://web.archive.org/web/20040702015452/http://www.bsi-global.com/Technical+Information/Publications/_Publications/tig90x.doc
    'BEL' => null,
    'BGJ' => null,
    'BGK' => null,
    'BGL' => 2, // https://web.archive.org/web/20040702015452/http://www.bsi-global.com/Technical+Information/Publications/_Publications/tig90x.doc
    'BGN' => 2, // https://web.archive.org/web/20250928170142/https://www.six-group.com/dam/download/financial-information/data-center/iso-currrency/lists/list-one.xml
    'BOP' => null,
    'BRB' => null,
    'BRC' => null,
    'BRE' => null,
    'BRN' => null,
    'BRR' => null,
    'BUK' => null,
    'BYB' => null,
    'BYR' => 0, // https://web.archive.org/web/20161201195250/https://www.currency-iso.org/dam/downloads/lists/list_one.xml
    'CHC' => null,
    'CSD' => null,
    'CSJ' => null,
    'CSK' => null,
    'CUC' => 2, // https://web.archive.org/web/20250128081422/https://www.six-group.com/dam/download/financial-information/data-center/iso-currrency/lists/list-one.xml
    'CYP' => 2, // https://web.archive.org/web/20040702015452/http://www.bsi-global.com/Technical+Information/Publications/_Publications/tig90x.doc
    'DDM' => null,
    'DEM' => 2, // https://web.archive.org/web/20040702015452/http://www.bsi-global.com/Technical+Information/Publications/_Publications/tig90x.doc
    'ECS' => null,
    'ECV' => null,
    'EEK' => 2, // https://web.archive.org/web/20040702015452/http://www.bsi-global.com/Technical+Information/Publications/_Publications/tig90x.doc
    'ESA' => null,
    'ESB' => null,
    'ESP' => 0, // https://web.archive.org/web/20040702015452/http://www.bsi-global.com/Technical+Information/Publications/_Publications/tig90x.doc
    'EUR' => 2, // https://web.archive.org/web/20250928170142/https://www.six-group.com/dam/download/financial-information/data-center/iso-currrency/lists/list-one.xml
    'FIM' => 2, // https://web.archive.org/web/20040702015452/http://www.bsi-global.com/Technical+Information/Publications/_Publications/tig90x.doc
    'FRF' => 2, // https://web.archive.org/web/20040702015452/http://www.bsi-global.com/Technical+Information/Publications/_Publications/tig90x.doc
    'GEK' => null,
    'GHC' => 2, // https://web.archive.org/web/20040702015452/http://www.bsi-global.com/Technical+Information/Publications/_Publications/tig90x.doc
    'GHP' => null,
    'GNE' => null,
    'GNS' => null,
    'GQE' => null,
    'GRD' => 0, // https://web.archive.org/web/20040702015452/http://www.bsi-global.com/Technical+Information/Publications/_Publications/tig90x.doc
    'GWE' => null,
    'GWP' => 2, // https://web.archive.org/web/20040702015452/http://www.bsi-global.com/Technical+Information/Publications/_Publications/tig90x.doc
    'HRD' => null,
    'HRK' => 2, // https://web.archive.org/web/20221107144009/https://www.six-group.com/dam/download/financial-information/data-center/iso-currrency/lists/list-one.xml
    'IDR' => 2, // https://web.archive.org/web/20250928170142/https://www.six-group.com/dam/download/financial-information/data-center/iso-currrency/lists/list-one.xml
    'IEP' => 2, // https://web.archive.org/web/20040702015452/http://www.bsi-global.com/Technical+Information/Publications/_Publications/tig90x.doc
    'ILP' => null,
    'ILR' => null,
    'ISJ' => null,
    'ITL' => 0, // https://web.archive.org/web/20040702015452/http://www.bsi-global.com/Technical+Information/Publications/_Publications/tig90x.doc
    'LAJ' => null,
    'LSM' => null,
    'LTL' => 2, // https://web.archive.org/web/20130703061233/http://www.currency-iso.org/dam/downloads/dl_iso_table_a1.xml
    'LTT' => null,
    'LUC' => null,
    'LUF' => 0, // https://web.archive.org/web/20040702015452/http://www.bsi-global.com/Technical+Information/Publications/_Publications/tig90x.doc
    'LUL' => null,
    'LVL' => 2, // https://web.archive.org/web/20130703061233/http://www.currency-iso.org/dam/downloads/dl_iso_table_a1.xml
    'LVR' => null,
    'MGF' => 0, // https://web.archive.org/web/20040702015452/http://www.bsi-global.com/Technical+Information/Publications/_Publications/tig90x.doc
    'MLF' => null,
    'MRO' => 2, // https://web.archive.org/web/20170912004601/https://www.currency-iso.org/dam/downloads/lists/list_one.xml
    'MTL' => 2, // https://web.archive.org/web/20040702015452/http://www.bsi-global.com/Technical+Information/Publications/_Publications/tig90x.doc
    'MTP' => null,
    'MVQ' => null,
    'MWK' => 2, // https://web.archive.org/web/20250928170142/https://www.six-group.com/dam/download/financial-information/data-center/iso-currrency/lists/list-one.xml
    'MXP' => null,
    'MZE' => null,
    'MZM' => 2, // https://web.archive.org/web/20040702015452/http://www.bsi-global.com/Technical+Information/Publications/_Publications/tig90x.doc
    'NIC' => null,
    'NLG' => 2, // https://web.archive.org/web/20040702015452/http://www.bsi-global.com/Technical+Information/Publications/_Publications/tig90x.doc
    'PEH' => null,
    'PEI' => null,
    'PEN' => 2, // https://web.archive.org/web/20250928170142/https://www.six-group.com/dam/download/financial-information/data-center/iso-currrency/lists/list-one.xml
    'PES' => null,
    'PLZ' => null,
    'PTE' => 0, // https://web.archive.org/web/20040702015452/http://www.bsi-global.com/Technical+Information/Publications/_Publications/tig90x.doc
    'RHD' => null,
    'ROK' => null,
    'ROL' => 2, // https://web.archive.org/web/20040702015452/http://www.bsi-global.com/Technical+Information/Publications/_Publications/tig90x.doc
    'RON' => 2, // https://web.archive.org/web/20250928170142/https://www.six-group.com/dam/download/financial-information/data-center/iso-currrency/lists/list-one.xml
    'RUR' => 2, // https://web.archive.org/web/20040702015452/http://www.bsi-global.com/Technical+Information/Publications/_Publications/tig90x.doc
    'SDD' => 2, // https://web.archive.org/web/20040702015452/http://www.bsi-global.com/Technical+Information/Publications/_Publications/tig90x.doc
    'SDG' => 2, // https://web.archive.org/web/20250928170142/https://www.six-group.com/dam/download/financial-information/data-center/iso-currrency/lists/list-one.xml
    'SDP' => null,
    'SIT' => 2, // https://web.archive.org/web/20040702015452/http://www.bsi-global.com/Technical+Information/Publications/_Publications/tig90x.doc
    'SKK' => 2, // https://web.archive.org/web/20040702015452/http://www.bsi-global.com/Technical+Information/Publications/_Publications/tig90x.doc
    'SLL' => 2, // https://web.archive.org/web/20230605125714/https://www.six-group.com/dam/download/financial-information/data-center/iso-currrency/lists/list-one.xml
    'SRG' => 2, // https://web.archive.org/web/20040702015452/http://www.bsi-global.com/Technical+Information/Publications/_Publications/tig90x.doc
    'STD' => 2, // https://web.archive.org/web/20170912004601/https://www.currency-iso.org/dam/downloads/lists/list_one.xml
    'SUR' => null,
    'SZL' => 2, // https://web.archive.org/web/20250928170142/https://www.six-group.com/dam/download/financial-information/data-center/iso-currrency/lists/list-one.xml
    'TJR' => null,
    'TMM' => 2, // https://web.archive.org/web/20040702015452/http://www.bsi-global.com/Technical+Information/Publications/_Publications/tig90x.doc
    'TPE' => 0, // https://web.archive.org/web/20040702015452/http://www.bsi-global.com/Technical+Information/Publications/_Publications/tig90x.doc
    'TRL' => 0, // https://web.archive.org/web/20040702015452/http://www.bsi-global.com/Technical+Information/Publications/_Publications/tig90x.doc
    'TRY' => 2, // https://web.archive.org/web/20250928170142/https://www.six-group.com/dam/download/financial-information/data-center/iso-currrency/lists/list-one.xml
    'UAK' => null,
    'UGS' => null,
    'UGW' => null,
    'USS' => 2, // https://web.archive.org/web/20130703061233/http://www.currency-iso.org/dam/downloads/dl_iso_table_a1.xml
    'UYN' => null,
    'UYP' => null,
    'VEB' => 2, // https://web.archive.org/web/20040702015452/http://www.bsi-global.com/Technical+Information/Publications/_Publications/tig90x.doc
    'VEF' => 2, // https://web.archive.org/web/20180511154730/https://www.currency-iso.org/dam/downloads/lists/list_one.xml
    'VNC' => null,
    'XEU' => null,
    'XFO' => null,
    'XFU' => null,
    'XRE' => null,
    'YDD' => null,
    'YUD' => null,
    'YUM' => 2, // https://web.archive.org/web/20040702015452/http://www.bsi-global.com/Technical+Information/Publications/_Publications/tig90x.doc
    'YUN' => null,
    'ZAL' => null,
    'ZMK' => 2, // https://web.archive.org/web/20040702015452/http://www.bsi-global.com/Technical+Information/Publications/_Publications/tig90x.doc
    'ZRN' => null,
    'ZRZ' => null,
    'ZWC' => null,
    'ZWD' => 2, // https://web.archive.org/web/20040702015452/http://www.bsi-global.com/Technical+Information/Publications/_Publications/tig90x.doc
    'ZWL' => 2, // https://web.archive.org/web/20240722165054/https://www.six-group.com/dam/download/financial-information/data-center/iso-currrency/lists/list-one.xml
    'ZWN' => null,
    'ZWR' => null,
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
