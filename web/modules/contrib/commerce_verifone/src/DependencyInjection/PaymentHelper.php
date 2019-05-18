<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is released under commercial license by Lamia Oy.
 *
 * @copyright Copyright (c) 2018 Lamia Oy (https://lamia.fi)
 */


namespace Drupal\commerce_verifone\DependencyInjection;


class PaymentHelper
{

  const GATEWAY_MODE_TEST = 'test';
  const GATEWAY_MODE_LIVE = 'live';

  const KEY_FILE_SHOP = 'shop';
  const KEY_FILE_GATEWAY = 'gateway';

  const PAY_PAGE_URL_DEMO = 'https://epayment.test.point.fi/pw/payment';
  const SERVER_URL_DEMO = 'https://epayment.test.point.fi/pw/serverinterface';

  const SERVER_URL_1 = 'https://epayment1.point.fi/pw/serverinterface';
  const SERVER_URL_2 = 'https://epayment2.point.fi/pw/serverinterface';
  const SERVER_URL_3 = 'https://epayment3.point.fi/pw/serverinterface';

  /**
   * Convert currency code to ISO 4217 number
   *
   * @param string $shopCurrency
   * @return string
   */
  public function convertCountryToISO4217($shopCurrency = 'EUR')
  {
    // http://en.wikipedia.org/wiki/ISO_4217
    $currency = [
      'AFA' => ['Afghan Afghani', '971'],
      'AWG' => ['Aruban Florin', '533'],
      'AUD' => ['Australian Dollars', '036'],
      'ARS' => ['Argentine Pes', '032'],
      'AZN' => ['Azerbaijanian Manat', '944'],
      'BSD' => ['Bahamian Dollar', '044'],
      'BDT' => ['Bangladeshi Taka', '050'],
      'BBD' => ['Barbados Dollar', '052'],
      'BYR' => ['Belarussian Rouble', '974'],
      'BOB' => ['Bolivian Boliviano', '068'],
      'BRL' => ['Brazilian Real', '986'],
      'GBP' => ['British Pounds Sterling', '826'],
      'BGN' => ['Bulgarian Lev', '975'],
      'KHR' => ['Cambodia Riel', '116'],
      'CAD' => ['Canadian Dollars', '124'],
      'KYD' => ['Cayman Islands Dollar', '136'],
      'CLP' => ['Chilean Peso', '152'],
      'CNY' => ['Chinese Renminbi Yuan', '156'],
      'COP' => ['Colombian Peso', '170'],
      'CRC' => ['Costa Rican Colon', '188'],
      'HRK' => ['Croatia Kuna', '191'],
      'CPY' => ['Cypriot Pounds', '196'],
      'CZK' => ['Czech Koruna', '203'],
      'DKK' => ['Danish Krone', '208'],
      'DOP' => ['Dominican Republic Peso', '214'],
      'XCD' => ['East Caribbean Dollar', '951'],
      'EGP' => ['Egyptian Pound', '818'],
      'ERN' => ['Eritrean Nakfa', '232'],
      'EEK' => ['Estonia Kroon', '233'],
      'EUR' => ['Euro', '978'],
      'GEL' => ['Georgian Lari', '981'],
      'GHC' => ['Ghana Cedi', '288'],
      'GIP' => ['Gibraltar Pound', '292'],
      'GTQ' => ['Guatemala Quetzal', '320'],
      'HNL' => ['Honduras Lempira', '340'],
      'HKD' => ['Hong Kong Dollars', '344'],
      'HUF' => ['Hungary Forint', '348'],
      'ISK' => ['Icelandic Krona', '352'],
      'INR' => ['Indian Rupee', '356'],
      'IDR' => ['Indonesia Rupiah', '360'],
      'ILS' => ['Israel Shekel', '376'],
      'JMD' => ['Jamaican Dollar', '388'],
      'JPY' => ['Japanese yen', '392'],
      'KZT' => ['Kazakhstan Tenge', '368'],
      'KES' => ['Kenyan Shilling', '404'],
      'KWD' => ['Kuwaiti Dinar', '414'],
      'LVL' => ['Latvia Lat', '428'],
      'LBP' => ['Lebanese Pound', '422'],
      'LTL' => ['Lithuania Litas', '440'],
      'MOP' => ['Macau Pataca', '446'],
      'MKD' => ['Macedonian Denar', '807'],
      'MGA' => ['Malagascy Ariary', '969'],
      'MYR' => ['Malaysian Ringgit', '458'],
      'MTL' => ['Maltese Lira', '470'],
      'BAM' => ['Marka', '977'],
      'MUR' => ['Mauritius Rupee', '480'],
      'MXN' => ['Mexican Pesos', '484'],
      'MZM' => ['Mozambique Metical', '508'],
      'NPR' => ['Nepalese Rupee', '524'],
      'ANG' => ['Netherlands Antilles Guilder', '532'],
      'TWD' => ['New Taiwanese Dollars', '901'],
      'NZD' => ['New Zealand Dollars', '554'],
      'NIO' => ['Nicaragua Cordoba', '558'],
      'NGN' => ['Nigeria Naira', '566'],
      'KPW' => ['North Korean Won', '408'],
      'NOK' => ['Norwegian Krone', '578'],
      'OMR' => ['Omani Riyal', '512'],
      'PKR' => ['Pakistani Rupee', '586'],
      'PYG' => ['Paraguay Guarani', '600'],
      'PEN' => ['Peru New Sol', '604'],
      'PHP' => ['Philippine Pesos', '608'],
      'PLN' => ['Polish złoty', '985'],
      'QAR' => ['Qatari Riyal', '634'],
      'RON' => ['Romanian New Leu', '946'],
      'RUB' => ['Russian Federation Ruble', '643'],
      'SAR' => ['Saudi Riyal', '682'],
      'CSD' => ['Serbian Dinar', '891'],
      'SCR' => ['Seychelles Rupee', '690'],
      'SGD' => ['Singapore Dollars', '702'],
      'SKK' => ['Slovak Koruna', '703'],
      'SIT' => ['Slovenia Tolar', '705'],
      'ZAR' => ['South African Rand', '710'],
      'KRW' => ['South Korean Won', '410'],
      'LKR' => ['Sri Lankan Rupee', '144'],
      'SRD' => ['Surinam Dollar', '968'],
      'SEK' => ['Swedish Krona', '752'],
      'CHF' => ['Swiss Francs', '756'],
      'TZS' => ['Tanzanian Shilling', '834'],
      'THB' => ['Thai Baht', '764'],
      'TTD' => ['Trinidad and Tobago Dollar', '780'],
      'TRY' => ['Turkish New Lira', '949'],
      'AED' => ['UAE Dirham', '784'],
      'USD' => ['US Dollars', '840'],
      'UGX' => ['Ugandian Shilling', '800'],
      'UAH' => ['Ukraine Hryvna', '980'],
      'UYU' => ['Uruguayan Peso', '858'],
      'UZS' => ['Uzbekistani Som', '860'],
      'VEB' => ['Venezuela Bolivar', '862'],
      'VND' => ['Vietnam Dong', '704'],
      'AMK' => ['Zambian Kwacha', '894'],
      'ZWD' => ['Zimbabwe Dollar', '716'],
    ];

    if (isset($currency[$shopCurrency][1])) {
      return $currency[$shopCurrency][1];
    } else {
      return $currency['EUR'][1];  // default to EUR
    }
  }

  /**
   * Convert country code to number
   *
   * @param string $cc
   * @return string
   */
  public function convertCountryCode2Numeric($cc)
  {

    $cc = strtoupper($cc);

    $codes = [
      'AF' => 4, 'AL' => 8, 'DZ' => 12, 'AS' => 16, 'AD' => 20, 'AO' => 24, 'AI' => 660, 'AQ' => 10,
      'AG' => 28, 'AR' => 32, 'AM' => 51, 'AW' => 533, 'AU' => 36, 'AT' => 40, 'AZ' => 31, 'BS' => 44,
      'BH' => 48, 'BD' => 50, 'BB' => 52, 'BY' => 112, 'BE' => 56, 'BZ' => 84, 'BJ' => 204, 'BM' => 60,
      'BT' => 64, 'BO' => 68, 'BA' => 70, 'BW' => 72, 'BV' => 74, 'BR' => 76, 'IO' => 86, 'BN' => 96,
      'BG' => 100, 'BF' => 854, 'BI' => 108, 'KH' => 116, 'CM' => 120, 'CA' => 124, 'CV' => 132, 'KY' => 136,
      'CF' => 140, 'TD' => 148, 'CL' => 152, 'CN' => 156, 'CX' => 162, 'CC' => 166, 'CO' => 170, 'KM' => 174,
      'CG' => 178, 'CK' => 184, 'CR' => 188, 'CI' => 384, 'HR' => 191, 'CU' => 192, 'CY' => 196, 'CZ' => 203,
      'DK' => 208, 'DJ' => 262, 'DM' => 212, 'DO' => 214, 'TP' => 626, 'EC' => 218, 'EG' => 818, 'SV' => 222,
      'GQ' => 226, 'ER' => 232, 'EE' => 233, 'ET' => 231, 'FK' => 238, 'FO' => 234, 'FJ' => 242, 'FI' => 246,
      'FR' => 250, 'FX' => 249, 'GF' => 254, 'PF' => 258, 'TF' => 260, 'GA' => 266, 'GM' => 270, 'GE' => 268,
      'DE' => 276, 'GH' => 288, 'GI' => 292, 'GR' => 300, 'GL' => 304, 'GD' => 308, 'GP' => 312, 'GU' => 316,
      'GT' => 320, 'GN' => 324, 'GW' => 624, 'GY' => 328, 'HT' => 332, 'HM' => 334, 'VA' => 336, 'HN' => 340,
      'HK' => 344, 'HU' => 348, 'IS' => 352, 'IN' => 356, 'ID' => 360, 'IR' => 364, 'IQ' => 368, 'IE' => 372,
      'IL' => 376, 'IT' => 380, 'JM' => 388, 'JP' => 392, 'JO' => 400, 'KZ' => 398, 'KE' => 404, 'KI' => 296,
      'KP' => 408, 'KR' => 410, 'KW' => 414, 'KG' => 417, 'LA' => 418, 'LV' => 428, 'LB' => 422, 'LS' => 426,
      'LR' => 430, 'LY' => 434, 'LI' => 438, 'LT' => 440, 'LU' => 442, 'MO' => 446, 'MK' => 807, 'MG' => 450,
      'MW' => 454, 'MY' => 458, 'MV' => 462, 'ML' => 466, 'MT' => 470, 'MH' => 584, 'MQ' => 474, 'MR' => 478,
      'MU' => 480, 'YT' => 175, 'MX' => 484, 'FM' => 583, 'MD' => 498, 'MC' => 492, 'MN' => 496, 'MS' => 500,
      'MA' => 504, 'MZ' => 508, 'MM' => 104, 'NA' => 516, 'NR' => 520, 'NP' => 524, 'NL' => 528, 'AN' => 530,
      'NC' => 540, 'NZ' => 554, 'NI' => 558, 'NE' => 562, 'NG' => 566, 'NU' => 570, 'NF' => 574, 'MP' => 580,
      'NO' => 578, 'OM' => 512, 'PK' => 586, 'PW' => 585, 'PA' => 591, 'PG' => 598, 'PY' => 600, 'PE' => 604,
      'PH' => 608, 'PN' => 612, 'PL' => 616, 'PT' => 620, 'PR' => 630, 'QA' => 634, 'RE' => 638, 'RO' => 642,
      'RU' => 643, 'RW' => 646, 'KN' => 659, 'LC' => 662, 'VC' => 670, 'WS' => 882, 'SM' => 674, 'ST' => 678,
      'SA' => 682, 'SN' => 686, 'SC' => 690, 'SL' => 694, 'SG' => 702, 'SK' => 703, 'SI' => 705, 'SB' => 90,
      'SO' => 706, 'ZA' => 710, 'GS' => 239, 'ES' => 724, 'LK' => 144, 'SH' => 654, 'PM' => 666, 'SD' => 736,
      'SR' => 740, 'SJ' => 744, 'SZ' => 748, 'SE' => 752, 'CH' => 756, 'SY' => 760, 'TW' => 158, 'TJ' => 762,
      'TZ' => 834, 'TH' => 764, 'TG' => 768, 'TK' => 772, 'TO' => 776, 'TT' => 780, 'TN' => 788, 'TR' => 792,
      'TM' => 795, 'TC' => 796, 'TV' => 798, 'UG' => 800, 'UA' => 804, 'AE' => 784, 'GB' => 826, 'US' => 840,
      'UM' => 581, 'UY' => 858, 'UZ' => 860, 'VU' => 548, 'VE' => 862, 'VN' => 704, 'VG' => 92, 'VI' => 850,
      'WF' => 876, 'EH' => 732, 'YE' => 887, 'YU' => 891, 'ZR' => 180, 'ZM' => 894, 'ZW' => 716];

    if (isset($codes[$cc])) {
      return $codes[$cc];
    } else {
      return $codes['FI'];  // default to Finland
    }
  }

  public function getSystemName()
  {
    return 'DrupalCommerce';
  }

  public function getModuleVersion()
  {
    $module = system_get_info('module', 'commerce');

    if (empty($module) || empty($module['version'])) {
      return '';
    }

    return $module['version'];

  }

  public function getMerchantId($gatewayId, $configuration, $defaultConfiguration)
  {
    $helper = new ConfigurationHelper($gatewayId, $configuration, $defaultConfiguration);

    return $helper->getMerchantAgreement();
  }

  public function getKeyPath($gatewayId, $configuration, $defaultConfiguration, $type)
  {

    $helper = new ConfigurationHelper($gatewayId, $configuration, $defaultConfiguration);
    if($type === self::KEY_FILE_GATEWAY) {
      return $helper->getPaymentPublicKeyFile();
    }

    return $helper->getShopPrivateKeyFile();

  }

  /**
   * Get urls to payment and server service for test and live environments.
   *
   * @param $type [server, page]
   * @return array
   */
  public function getUrls($configuration, $type)
  {

    if ($configuration['mode'] === self::GATEWAY_MODE_LIVE) {

      if ($type === 'server') {
        return [self::SERVER_URL_1, self::SERVER_URL_2, self::SERVER_URL_3];
      } elseif ($type === 'page') {
        $urls = [];
        for ($i = 1; $i <= 3; $i++) {
          $url = $configuration['pay_page_url_' . $i];
          if (isset($url) && !empty($url)) {
            $urls[] = $url;
          }
        }
        return $urls;
      }

    } else {

      if ($type === 'server') {
        return [self::SERVER_URL_DEMO];
      } elseif ($type === 'page') {
        return [self::PAY_PAGE_URL_DEMO];
      }

    }

    return [];
  }
}