<?php

namespace Drupal\sms_ui\Utility;

use Drupal\Core\StringTranslation\TranslatableMarkup;

class CountryCodes {

  /**
   * @var \Drupal\sms_ui\Utility\CountryCodes
   */
  protected static $singleton;

  /**
   * An of cached country codes which have already be processed.
   *
   * @var array
   */
  protected static $cachedCodes;

  /**
   * An array of arrays containing information on country codes.
   *
   * @var array
   */
  protected $countryCodes;

  /**
   * Return the country code and country for this number
   *
   * If $number is an array, an array will be returned containing corresponding codes
   */
  public static function getCountryCode($number) {
    $codes = array_reverse(static::getCountryCodes('normal', 'code'), true);

    if (!is_array($number)) {
      $num = ltrim($number, '0+ ');

      // Return -1 for local numbers
      if (strlen($num) < 11) {
        return -1;
      }

      foreach ($codes as $code => $country) {
        if (strpos($num, (string) $code) === 0) {
          return $code;
        }
      }
      return NULL;
    }
    else {
      $ret = array();
      foreach ($number as $num) {
        $num1 = ltrim($num, '0+ ');

        // Return -1 for local numbers
        if (strlen($num1) < 11) {
          $ret[$num] = -1;
        }
        else {
          foreach ($codes as $code => $country) {
            if (strpos($num1, $code) === 0) {
              $ret[$num] = $code;
              break;
            }
          }
        }
      }
      return $ret;
    }
  }

  /**
   * Return the country code for the specified country
   */
  public static function getCodeForCountry($country) {
    $codes = static::getCountryCodes('reverse')[(string)$country];
    return static::getCountryCodes('reverse')[(string)$country];
  }

  /**
   * Return the country name for the specified country code
   */
  public static function getCountryForCode($code) {
    return static::getCountryCodes('normal')[$code];
  }

  /**
   * Returns TRUE if this is a valid country code
   */
  public static function isValidCode($code) {
    return static::getCountryForCode($code) != '';
  }

  /**
   * Returns a list of the country codes with names.
   *
   * @param string $format
   * (optional) The format of the return value. One of the following:
   *   - normal: return as array of country names keyed by the codes.
   *   - reverse: return as array of country codes keyed by the names.
   *   - table (default): return as array of arrays suitable to be used in render
   *   array of type '#table'
   * @param string $sort_by
   *   The sorting priority - either by country 'name' or 'code'
   *
   * @return array
   */
  public static function getCountryCodes($format = 'normal', $sort_by = 'name') {
    if (!isset(static::$singleton)) {
      static::$singleton = new CountryCodes();
    }
    if (!isset(static::$cachedCodes)) {
      static::$cachedCodes = array();
    }
    if (!isset(static::$cachedCodes[$sort_by])) {
      $cc = static::$singleton->countryCodes();
      usort($cc, function($a, $b) use ($sort_by) {
        switch ($sort_by) {
          case 'name':
            return strcmp($a[0], $b[0]);
          case 'code':
          default:
            return strcmp($a[1], $b[1]);
        }
      });
      static::$cachedCodes[$sort_by] = $cc;
    }

    switch ($format) {
      case 'normal':
        $cc1 = array();
        foreach (static::$cachedCodes[$sort_by] as $v) {
          $cc1[$v[1]] = $v[0];
        }
        return $cc1;

      case 'reverse':
        $cc1 = array();
        foreach (static::$cachedCodes[$sort_by] as $v) {
          $key = (string) $v[0];
          $cc1[$key] = $v[1];
        }
        return $cc1;

      case 'table':
      default:
        return static::$cachedCodes[$sort_by];
    }
  }

  protected function countryCodes() {
    if (!isset($this->countryCodes)) {
      $this->countryCodes = array(
        array(new TranslatableMarkup('Afghanistan'), 93),
        array(new TranslatableMarkup('Albania'), 355),
        array(new TranslatableMarkup('Algeria'), 213),
        array(new TranslatableMarkup('Andorra'), 376),
        array(new TranslatableMarkup('Angola'), 244),
        array(new TranslatableMarkup('Anguilla'), 1264),
        array(new TranslatableMarkup('Antigua & Barbuda'), 1268),
        array(new TranslatableMarkup('Argentina'), 54),
        array(new TranslatableMarkup('Armenia'), 374),
        array(new TranslatableMarkup('Aruba'), 297),
        array(new TranslatableMarkup('Australia'), 61),
        array(new TranslatableMarkup('Austria'), 43),
        array(new TranslatableMarkup('Azerbaijan'), 994),
        array(new TranslatableMarkup('Bahamas'), 1242),
        array(new TranslatableMarkup('Bahrain'), 973),
        array(new TranslatableMarkup('Bangladesh'), 880),
        array(new TranslatableMarkup('Barbados'), 1246),
        array(new TranslatableMarkup('Belarus'), 375),
        array(new TranslatableMarkup('Belgium'), 32),
        array(new TranslatableMarkup('Belize'), 501),
        array(new TranslatableMarkup('Benin'), 229),
        array(new TranslatableMarkup('Bermuda'), 1441),
        array(new TranslatableMarkup('Bhutan'), 975),
        array(new TranslatableMarkup('Bolivia'), 591),
        array(new TranslatableMarkup('Bosnia-Herzegovina'), 387),
        array(new TranslatableMarkup('Botswana'), 267),
        array(new TranslatableMarkup('Brazil'), 55),
        array(new TranslatableMarkup('British Virgin Islands'), 1284),
        array(new TranslatableMarkup('Brunei'), 673),
        array(new TranslatableMarkup('Bulgaria'), 359),
        array(new TranslatableMarkup('Burkina Faso'), 226),
        array(new TranslatableMarkup('Burundi'), 257),
        array(new TranslatableMarkup('Cambodia'), 855),
        array(new TranslatableMarkup('Cameroon'), 237),
        array(new TranslatableMarkup('Canary Islands'), 34),
        array(new TranslatableMarkup('Cape Verde'), 238),
        array(new TranslatableMarkup('Cayman Islands'), 1345),
        array(new TranslatableMarkup('Central African Republic'), 236),
        array(new TranslatableMarkup('Chad'), 235),
        array(new TranslatableMarkup('Chile'), 56),
        array(new TranslatableMarkup('China'), 86),
        array(new TranslatableMarkup('Colombia'), 57),
        array(new TranslatableMarkup('Comoros'), 269),
        array(new TranslatableMarkup('Congo'), 242),
        array(new TranslatableMarkup('Democratic Republic Congo'), 243),
        array(new TranslatableMarkup('Cook Islands'), 682),
        array(new TranslatableMarkup('Croatia'), 385),
        array(new TranslatableMarkup('Cuba'), 53),
        array(new TranslatableMarkup('Cyprus'), 357),
        array(new TranslatableMarkup('Czech Republic'), 420),
        array(new TranslatableMarkup('Denmark'), 45),
        array(new TranslatableMarkup('Djibouti'), 253),
        array(new TranslatableMarkup('Dominica'), 1767),
        array(new TranslatableMarkup('East Timor'), 670),
        array(new TranslatableMarkup('Ecuador'), 593),
        array(new TranslatableMarkup('Egypt'), 20),
        array(new TranslatableMarkup('El Salvador'), 503),
        array(new TranslatableMarkup('Equatorial Guinea'), 240),
        array(new TranslatableMarkup('Estonia'), 372),
        array(new TranslatableMarkup('Ethiopia'), 251),
        array(new TranslatableMarkup('Falkland Islands'), 500),
        array(new TranslatableMarkup('Faroe Islands'), 298),
        array(new TranslatableMarkup('Fiji'), 679),
        array(new TranslatableMarkup('Finland'), 358),
        array(new TranslatableMarkup('France'), 33),
        array(new TranslatableMarkup('French Guiana'), 594),
        array(new TranslatableMarkup('French Polynesia'), 689),
        array(new TranslatableMarkup('Gabon'), 241),
        array(new TranslatableMarkup('Gambia'), 220),
        array(new TranslatableMarkup('Georgia'), 995),
        array(new TranslatableMarkup('Germany'), 49),
        array(new TranslatableMarkup('Ghana'), 233),
        array(new TranslatableMarkup('Gibraltar'), 350),
        array(new TranslatableMarkup('Global Mobile Satellite'), 881),
        array(new TranslatableMarkup('Greece'), 30),
        array(new TranslatableMarkup('Greenland'), 299),
        array(new TranslatableMarkup('Grenada'), 1473),
        array(new TranslatableMarkup('Guadeloupe'), 590),
        array(new TranslatableMarkup('Guam'), 1671),
        array(new TranslatableMarkup('Guatemala'), 502),
        array(new TranslatableMarkup('Guinea'), 224),
        array(new TranslatableMarkup('Guyana'), 592),
        array(new TranslatableMarkup('Haiti'), 509),
        array(new TranslatableMarkup('Honduras'), 504),
        array(new TranslatableMarkup('HongKong'), 852),
        array(new TranslatableMarkup('Hungary'), 36),
        array(new TranslatableMarkup('Iceland'), 354),
        array(new TranslatableMarkup('India'), 91),
        array(new TranslatableMarkup('Indonesia'), 62),
        array(new TranslatableMarkup('Iran'), 98),
        array(new TranslatableMarkup('Iraq'), 964),
        array(new TranslatableMarkup('Ireland'), 353),
        array(new TranslatableMarkup('Israel'), 972),
        array(new TranslatableMarkup('Italy / Vatican City State'), 39),
        array(new TranslatableMarkup('Ivory Coast'), 225),
        array(new TranslatableMarkup('Jamaica'), 1876),
        array(new TranslatableMarkup('Japan'), 81),
        array(new TranslatableMarkup('Jordan'), 962),
        array(new TranslatableMarkup('Kenya'), 254),
        array(new TranslatableMarkup('Korea (South)'), 82),
        array(new TranslatableMarkup('Kuwait'), 965),
        array(new TranslatableMarkup('Kyrgyzstan'), 996),
        array(new TranslatableMarkup('Lao'), 856),
        array(new TranslatableMarkup('Latvia'), 371),
        array(new TranslatableMarkup('Lebanon'), 961),
        array(new TranslatableMarkup('Lesotho'), 266),
        array(new TranslatableMarkup('Liberia'), 231),
        array(new TranslatableMarkup('Libya'), 218),
        array(new TranslatableMarkup('Liechtenstein'), 423),
        array(new TranslatableMarkup('Lithuania'), 370),
        array(new TranslatableMarkup('Luxembourg'), 352),
        array(new TranslatableMarkup('Macau'), 853),
        array(new TranslatableMarkup('Macedonia'), 389),
        array(new TranslatableMarkup('Madagascar'), 261),
        array(new TranslatableMarkup('Malawi'), 265),
        array(new TranslatableMarkup('Malaysia'), 60),
        array(new TranslatableMarkup('Maldives'), 960),
        array(new TranslatableMarkup('Mali'), 223),
        array(new TranslatableMarkup('Malta'), 356),
        array(new TranslatableMarkup('Martinique'), 596),
        array(new TranslatableMarkup('Mauritania'), 222),
        array(new TranslatableMarkup('Mauritius'), 230),
        array(new TranslatableMarkup('Mayotte Island (Comoros)'), 269),
        array(new TranslatableMarkup('Mexico'), 52),
        array(new TranslatableMarkup('Moldova'), 373),
        array(new TranslatableMarkup('Monaco (Kosovo)'), 377),
        array(new TranslatableMarkup('Mongolia'), 976),
        array(new TranslatableMarkup('Montenegro'), 382),
        array(new TranslatableMarkup('Montserrat'), 1664),
        array(new TranslatableMarkup('Morocco'), 212),
        array(new TranslatableMarkup('Mozambique'), 258),
        array(new TranslatableMarkup('Myanmar'), 95),
        array(new TranslatableMarkup('Namibia'), 264),
        array(new TranslatableMarkup('Nepal'), 977),
        array(new TranslatableMarkup('Netherlands'), 31),
        array(new TranslatableMarkup('Netherlands Antilles'), 599),
        array(new TranslatableMarkup('New Caledonia'), 687),
        array(new TranslatableMarkup('New Zealand'), 64),
        array(new TranslatableMarkup('Nicaragua'), 505),
        array(new TranslatableMarkup('Niger'), 227),
        array(new TranslatableMarkup('Nigeria'), 234),
        array(new TranslatableMarkup('Norway'), 47),
        array(new TranslatableMarkup('Oman'), 968),
        array(new TranslatableMarkup('Pakistan'), 92),
        array(new TranslatableMarkup('Palestine (+970)'), 970),
        array(new TranslatableMarkup('Palestine (+9725)'), 9725),
        array(new TranslatableMarkup('Panama'), 507),
        array(new TranslatableMarkup('Papua New Guinea'), 675),
        array(new TranslatableMarkup('Paraguay'), 595),
        array(new TranslatableMarkup('Peru'), 51),
        array(new TranslatableMarkup('Philippines'), 63),
        array(new TranslatableMarkup('Poland'), 48),
        array(new TranslatableMarkup('Portugal'), 351),
        array(new TranslatableMarkup('Qatar'), 974),
        array(new TranslatableMarkup('Reunion'), 262),
        array(new TranslatableMarkup('Romania'), 40),
        array(new TranslatableMarkup('Russia / Kazakhstan'), 7),
        array(new TranslatableMarkup('Rwanda'), 250),
        array(new TranslatableMarkup('Saipan'), 1670),
        array(new TranslatableMarkup('Samoa (American)'), 1684),
        array(new TranslatableMarkup('Samoa (Western)'), 685),
        array(new TranslatableMarkup('San Marino'), 378),
        array(new TranslatableMarkup('Satellite-Thuraya'), 882),
        array(new TranslatableMarkup('Saudi Arabia'), 966),
        array(new TranslatableMarkup('Senegal'), 221),
        array(new TranslatableMarkup('Serbia'), 381),
        array(new TranslatableMarkup('Seychelles'), 248),
        array(new TranslatableMarkup('Sierra Leone'), 232),
        array(new TranslatableMarkup('Singapore'), 65),
        array(new TranslatableMarkup('Slovakia'), 421),
        array(new TranslatableMarkup('Slovenia'), 386),
        array(new TranslatableMarkup('Somalia'), 252),
        array(new TranslatableMarkup('South Africa'), 27),
        array(new TranslatableMarkup('Spain'), 34),
        array(new TranslatableMarkup('Sri Lanka'), 94),
        array(new TranslatableMarkup('St. Kitts And Nevis'), 1869),
        array(new TranslatableMarkup('St. Lucia'), 1758),
        array(new TranslatableMarkup('St. Vincent'), 1784),
        array(new TranslatableMarkup('Sudan'), 249),
        array(new TranslatableMarkup('Suriname'), 597),
        array(new TranslatableMarkup('Swaziland'), 268),
        array(new TranslatableMarkup('Sweden'), 46),
        array(new TranslatableMarkup('Switzerland'), 41),
        array(new TranslatableMarkup('Syria'), 963),
        array(new TranslatableMarkup('Taiwan'), 886),
        array(new TranslatableMarkup('Tajikistan'), 992),
        array(new TranslatableMarkup('Tanzania'), 255),
        array(new TranslatableMarkup('Thailand'), 66),
        array(new TranslatableMarkup('Togo'), 228),
        array(new TranslatableMarkup('Tonga Islands'), 676),
        array(new TranslatableMarkup('Trinidad and Tobago'), 1868),
        array(new TranslatableMarkup('Tunisia'), 216),
        array(new TranslatableMarkup('Turkey'), 90),
        array(new TranslatableMarkup('Turkmenistan'), 993),
        array(new TranslatableMarkup('Turks and Caicos Islands'), 1649),
        array(new TranslatableMarkup('Uganda'), 256),
        array(new TranslatableMarkup('UK / Isle of Man / Jersey / Guernsey'), 44),
        array(new TranslatableMarkup('Ukraine'), 380),
        array(new TranslatableMarkup('United Arab Emirates'), 971),
        array(new TranslatableMarkup('Uruguay'), 598),
        array(new TranslatableMarkup('USA / Canada / Dominican Rep. / Puerto Rico'), 1),
        array(new TranslatableMarkup('Uzbekistan'), 998),
        array(new TranslatableMarkup('Vanuatu'), 678),
        array(new TranslatableMarkup('Venezuela'), 58),
        array(new TranslatableMarkup('Vietnam'), 84),
        array(new TranslatableMarkup('Yemen'), 967),
        array(new TranslatableMarkup('Zambia'), 260),
        array(new TranslatableMarkup('Zanzibar'), 255),
        array(new TranslatableMarkup('Zimbabwe'), 263),
      );
    }
    return $this->countryCodes;
  }

}
