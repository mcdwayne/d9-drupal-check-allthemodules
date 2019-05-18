<?php

namespace Drupal\google_currency_converter;

use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class GoogleCurrencyConverterManager.
 *
 * @package Drupal\google_currency_converter
 */
class GoogleCurrencyConverterManager implements GoogleCurrencyConverterManagerInterface {

  use StringTranslationTrait;

  /**
   * Constructs a new Entity plugin manager.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation.
   */
  public function __construct(TranslationInterface $string_translation) {
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function convertAmount($amount, $from, $to) {
    // Google currency converter widget
    // Base URL: https://www.google.com/finance/converter
    // Query Parameters:
    // - a: amount to be converted.
    // - from: source currency.
    // - to: target currency.
    $url = "https://www.google.com/finance/converter?a=$amount&from=$from&to=$to";

    // Fetches widget HTML with converted amount.
    $data = file_get_contents($url);

    // According to Google's current layout, it provides converted amount with
    // target currency code within `span` tag with class `bld`.
    // example: <span class=bld>1.5 USD</span>
    // So, we extract converted amount using `preg_match` function.
    preg_match("/<span class=bld>(.*)<\/span>/", $data, $converted);

    // Along with amount, we have target currency code. We use `preg_replace`
    // function to remove target currency code and extract only numeric value.
    $converted = preg_replace("/[^0-9.]/", "", isset($converted[1]) ? $converted[1] : NULL);

    return round($converted, 2);
  }

  /**
   * {@inheritdoc}
   */
  public function countries() {
    $countries = [
      'AED' => $this->t('United Arab Emirates Dirham (AED)'),
      'AFN' => $this->t('Afghan Afghani (AFN)'),
      'ALL' => $this->t('Albanian Lek (ALL)'),
      'AMD' => $this->t('Armenian Dram (AMD)'),
      'ANG' => $this->t('Netherlands Antillean Guilder (ANG)'),
      'AOA' => $this->t('Angolan Kwanza (AOA)'),
      'ARS' => $this->t('Argentine Peso (ARS)'),
      'AUD' => $this->t('Australian Dollar (A$)'),
      'AWG' => $this->t('Aruban Florin (AWG)'),
      'AZN' => $this->t('Azerbaijani Manat (AZN)'),
      'BAM' => $this->t('Bosnia-Herzegovina Convertible Mark (BAM)'),
      'BBD' => $this->t('Barbadian Dollar (BBD)'),
      'BDT' => $this->t('Bangladeshi Taka (BDT)'),
      'BGN' => $this->t('Bulgarian Lev (BGN)'),
      'BHD' => $this->t('Bahraini Dinar (BHD)'),
      'BIF' => $this->t('Burundian Franc (BIF)'),
      'BMD' => $this->t('Bermudan Dollar (BMD)'),
      'BND' => $this->t('Brunei Dollar (BND)'),
      'BOB' => $this->t('Bolivian Boliviano (BOB)'),
      'BRL' => $this->t('Brazilian Real (R$)'),
      'BSD' => $this->t('Bahamian Dollar (BSD)'),
      'BTC' => $this->t('Bitcoin (฿)'),
      'BTN' => $this->t('Bhutanese Ngultrum (BTN)'),
      'BWP' => $this->t('Botswanan Pula (BWP)'),
      'BYR' => $this->t('Belarusian Ruble (BYR)'),
      'BZD' => $this->t('Belize Dollar (BZD)'),
      'CAD' => $this->t('Canadian Dollar (CA$)'),
      'CDF' => $this->t('Congolese Franc (CDF)'),
      'CHF' => $this->t('Swiss Franc (CHF)'),
      'CLF' => $this->t('Chilean Unit of Account (UF) (CLF)'),
      'CLP' => $this->t('Chilean Peso (CLP)'),
      'CNH' => $this->t('CNH (CNH)'),
      'CNY' => $this->t('Chinese Yuan (CN¥)'),
      'COP' => $this->t('Colombian Peso (COP)'),
      'CRC' => $this->t('Costa Rican Colón (CRC)'),
      'CUP' => $this->t('Cuban Peso (CUP)'),
      'CVE' => $this->t('Cape Verdean Escudo (CVE)'),
      'CZK' => $this->t('Czech Republic Koruna (CZK)'),
      'DEM' => $this->t('German Mark (DEM)'),
      'DJF' => $this->t('Djiboutian Franc (DJF)'),
      'DKK' => $this->t('Danish Krone (DKK)'),
      'DOP' => $this->t('Dominican Peso (DOP)'),
      'DZD' => $this->t('Algerian Dinar (DZD)'),
      'EGP' => $this->t('Egyptian Pound (EGP)'),
      'ERN' => $this->t('Eritrean Nakfa (ERN)'),
      'ETB' => $this->t('Ethiopian Birr (ETB)'),
      'EUR' => $this->t('Euro (€)'),
      'FIM' => $this->t('Finnish Markka (FIM)'),
      'FJD' => $this->t('Fijian Dollar (FJD)'),
      'FKP' => $this->t('Falkland Islands Pound (FKP)'),
      'FRF' => $this->t('French Franc (FRF)'),
      'GBP' => $this->t('British Pound (£)'),
      'GEL' => $this->t('Georgian Lari (GEL)'),
      'GHS' => $this->t('Ghanaian Cedi (GHS)'),
      'GIP' => $this->t('Gibraltar Pound (GIP)'),
      'GMD' => $this->t('Gambian Dalasi (GMD)'),
      'GNF' => $this->t('Guinean Franc (GNF)'),
      'GTQ' => $this->t('Guatemalan Quetzal (GTQ)'),
      'GYD' => $this->t('Guyanaese Dollar (GYD)'),
      'HKD' => $this->t('Hong Kong Dollar (HK$)'),
      'HNL' => $this->t('Honduran Lempira (HNL)'),
      'HRK' => $this->t('Croatian Kuna (HRK)'),
      'HTG' => $this->t('Haitian Gourde (HTG)'),
      'HUF' => $this->t('Hungarian Forint (HUF)'),
      'IDR' => $this->t('Indonesian Rupiah (IDR)'),
      'ILS' => $this->t('Israeli New Sheqel (₪)'),
      'INR' => $this->t('Indian Rupee (Rs.)'),
      'IQD' => $this->t('Iraqi Dinar (IQD)'),
      'IRR' => $this->t('Iranian Rial (IRR)'),
      'ISK' => $this->t('Icelandic Króna (ISK)'),
      'ITL' => $this->t('Italian Lira (ITL)'),
      'JMD' => $this->t('Jamaican Dollar (JMD)'),
      'JOD' => $this->t('Jordanian Dinar (JOD)'),
      'JPY' => $this->t('Japanese Yen (¥)'),
      'KES' => $this->t('Kenyan Shilling (KES)'),
      'KGS' => $this->t('Kyrgystani Som (KGS)'),
      'KHR' => $this->t('Cambodian Riel (KHR)'),
      'KMF' => $this->t('Comorian Franc (KMF)'),
      'KPW' => $this->t('North Korean Won (KPW)'),
      'KRW' => $this->t('South Korean Won (₩)'),
      'KWD' => $this->t('Kuwaiti Dinar (KWD)'),
      'KYD' => $this->t('Cayman Islands Dollar (KYD)'),
      'KZT' => $this->t('Kazakhstani Tenge (KZT)'),
      'LAK' => $this->t('Laotian Kip (LAK)'),
      'LBP' => $this->t('Lebanese Pound (LBP)'),
      'LKR' => $this->t('Sri Lankan Rupee (LKR)'),
      'LRD' => $this->t('Liberian Dollar (LRD)'),
      'LSL' => $this->t('Lesotho Loti (LSL)'),
      'LTL' => $this->t('Lithuanian Litas (LTL)'),
      'LVL' => $this->t('Latvian Lats (LVL)'),
      'LYD' => $this->t('Libyan Dinar (LYD)'),
      'MAD' => $this->t('Moroccan Dirham (MAD)'),
      'MDL' => $this->t('Moldovan Leu (MDL)'),
      'MGA' => $this->t('Malagasy Ariary (MGA)'),
      'MKD' => $this->t('Macedonian Denar (MKD)'),
      'MMK' => $this->t('Myanmar Kyat (MMK)'),
      'MNT' => $this->t('Mongolian Tugrik (MNT)'),
      'MOP' => $this->t('Macanese Pataca (MOP)'),
      'MRO' => $this->t('Mauritanian Ouguiya (MRO)'),
      'MUR' => $this->t('Mauritian Rupee (MUR)'),
      'MVR' => $this->t('Maldivian Rufiyaa (MVR)'),
      'MWK' => $this->t('Malawian Kwacha (MWK)'),
      'MXN' => $this->t('Mexican Peso (MX$)'),
      'MYR' => $this->t('Malaysian Ringgit (MYR)'),
      'MZN' => $this->t('Mozambican Metical (MZN)'),
      'NAD' => $this->t('Namibian Dollar (NAD)'),
      'NGN' => $this->t('Nigerian Naira (NGN)'),
      'NIO' => $this->t('Nicaraguan Córdoba (NIO)'),
      'NOK' => $this->t('Norwegian Krone (NOK)'),
      'NPR' => $this->t('Nepalese Rupee (NPR)'),
      'NZD' => $this->t('New Zealand Dollar (NZ$)'),
      'OMR' => $this->t('Omani Rial (OMR)'),
      'PAB' => $this->t('Panamanian Balboa (PAB)'),
      'PEN' => $this->t('Peruvian Nuevo Sol (PEN)'),
      'PGK' => $this->t('Papua New Guinean Kina (PGK)'),
      'PHP' => $this->t('Philippine Peso (Php)'),
      'PKG' => $this->t('PKG (PKG)'),
      'PKR' => $this->t('Pakistani Rupee (PKR)'),
      'PLN' => $this->t('Polish Zloty (PLN)'),
      'PYG' => $this->t('Paraguayan Guarani (PYG)'),
      'QAR' => $this->t('Qatari Rial (QAR)'),
      'RON' => $this->t('Romanian Leu (RON)'),
      'RSD' => $this->t('Serbian Dinar (RSD)'),
      'RUB' => $this->t('Russian Ruble (RUB)'),
      'RWF' => $this->t('Rwandan Franc (RWF)'),
      'SAR' => $this->t('Saudi Riyal (SAR)'),
      'SBD' => $this->t('Solomon Islands Dollar (SBD)'),
      'SCR' => $this->t('Seychellois Rupee (SCR)'),
      'SDG' => $this->t('Sudanese Pound (SDG)'),
      'SEK' => $this->t('Swedish Krona (SEK)'),
      'SGD' => $this->t('Singapore Dollar (SGD)'),
      'SHP' => $this->t('St. Helena Pound (SHP)'),
      'SKK' => $this->t('Slovak Koruna (SKK)'),
      'SLL' => $this->t('Sierra Leonean Leone (SLL)'),
      'SOS' => $this->t('Somali Shilling (SOS)'),
      'SRD' => $this->t('Surinamese Dollar (SRD)'),
      'STD' => $this->t('São Tomé &amp; Príncipe Dobra (STD)'),
      'SVC' => $this->t('Salvadoran Colón (SVC)'),
      'SYP' => $this->t('Syrian Pound (SYP)'),
      'SZL' => $this->t('Swazi Lilangeni (SZL)'),
      'THB' => $this->t('Thai Baht (THB)'),
      'TJS' => $this->t('Tajikistani Somoni (TJS)'),
      'TMT' => $this->t('Turkmenistani Manat (TMT)'),
      'TND' => $this->t('Tunisian Dinar (TND)'),
      'TOP' => $this->t('Tongan Paʻanga (TOP)'),
      'TRY' => $this->t('Turkish Lira (TRY)'),
      'TTD' => $this->t('Trinidad &amp; Tobago Dollar (TTD)'),
      'TWD' => $this->t('New Taiwan Dollar (NT$)'),
      'TZS' => $this->t('Tanzanian Shilling (TZS)'),
      'UAH' => $this->t('Ukrainian Hryvnia (UAH)'),
      'UGX' => $this->t('Ugandan Shilling (UGX)'),
      'USD' => $this->t('US Dollar ($)'),
      'UYU' => $this->t('Uruguayan Peso (UYU)'),
      'UZS' => $this->t('Uzbekistani Som (UZS)'),
      'VEF' => $this->t('Venezuelan Bolívar (VEF)'),
      'VND' => $this->t('Vietnamese Dong (₫)'),
      'VUV' => $this->t('Vanuatu Vatu (VUV)'),
      'WST' => $this->t('Samoan Tala (WST)'),
      'XAF' => $this->t('Central African CFA Franc (FCFA)'),
      'XCD' => $this->t('East Caribbean Dollar (EC$)'),
      'XDR' => $this->t('Special Drawing Rights (XDR)'),
      'XOF' => $this->t('West African CFA Franc (CFA)'),
      'XPF' => $this->t('CFP Franc (CFPF)'),
      'YER' => $this->t('Yemeni Rial (YER)'),
      'ZAR' => $this->t('South African Rand (ZAR)'),
      'ZMK' => $this->t('Zambian Kwacha (1968–2012) (ZMK)'),
      'ZMW' => $this->t('Zambian Kwacha (ZMW)'),
      'ZWL' => $this->t('Zimbabwean Dollar (2009) (ZWL)'),
    ];
    return $countries;
  }

}
