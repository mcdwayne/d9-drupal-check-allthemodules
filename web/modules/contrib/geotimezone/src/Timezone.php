<?php

/**
 * @file
 * Contains \Drupal\geotimezone\Timezone.
 */

namespace Drupal\geotimezone;

/**
 * Holds the list of time zone identifiers and UTC/GMT offsets.
 *
 * @package Drupal\geotimezone
 */
final class Timezone implements TimezoneInterface {
  /**
   * List of time zone identifiers and UTC/GMT offsets.
   *
   * @var array $list
   */
  private static $list;

  /**
   * Queried time zone identifier.
   *
   * @var string $identifier
   */
  private $identifier;

  /**
   * Queried time zone offset.
   *
   * @var string $offset
   */
  private $offset;

  /**
   * Timezone constructor.
   *
   * @param int $index
   *   Time zone list index.
   */
  public function __construct($index = 0) {
    static::$list = $this->loadList();
    $this->identifier = static::$list[$index]['identifier'];
    // Convert to time zone offset
    $time = new \DateTime('now', new \DateTimeZone($this->identifier));
    $this->offset = $time->format('P');
    //$this->offset = static::$list[$index]['offset'];
  }

  /**
   * {@inheritdoc}
   */
  public function getIdentifier() {
    return $this->identifier;
  }

  /**
   * {@inheritdoc}
   */
  public function getOffset() {
    return $this->offset;
  }

  /**
   * Loads the list of time zone identifiers and UTC/GMT offsets.
   *
   * @return array
   *   List of time zone identifiers and UTC/GMT offsets.
   */
  private function loadList() {
    return [
      0 => [
        'identifier' => NULL,
        'offset' => NULL,
      ],
      1 => [
        'identifier' => 'America/Dominica',
        'offset' => '-04:00',
      ],
      2 => [
        'identifier' => 'America/St_Vincent',
        'offset' => '-04:00',
      ],
      3 => [
        'identifier' => 'Australia/Lord_Howe',
        'offset' => '+10:30',
      ],
      4 => [
        'identifier' => 'Asia/Kashgar',
        'offset' => '+06:00',
      ],
      5 => [
        'identifier' => 'Pacific/Wallis',
        'offset' => '+12:00',
      ],
      6 => [
        'identifier' => 'Europe/Berlin',
        'offset' => '+02:00',
      ],
      7 => [
        'identifier' => 'America/Manaus',
        'offset' => '-04:00',
      ],
      8 => [
        'identifier' => 'Asia/Jerusalem',
        'offset' => '+03:00',
      ],
      9 => [
        'identifier' => 'America/Phoenix',
        'offset' => '-07:00',
      ],
      10 => [
        'identifier' => 'Australia/Darwin',
        'offset' => '+09:30',
      ],
      11 => [
        'identifier' => 'Asia/Seoul',
        'offset' => '+09:00',
      ],
      12 => [
        'identifier' => 'Africa/Gaborone',
        'offset' => '+02:00',
      ],
      13 => [
        'identifier' => 'Indian/Chagos',
        'offset' => '+06:00',
      ],
      14 => [
        'identifier' => 'America/Argentina/Mendoza',
        'offset' => '-03:00',
      ],
      15 => [
        'identifier' => 'Asia/Hong_Kong',
        'offset' => '+08:00',
      ],
      16 => [
        'identifier' => 'America/Godthab',
        'offset' => '-02:00',
      ],
      17 => [
        'identifier' => 'Africa/Dar_es_Salaam',
        'offset' => '+03:00',
      ],
      18 => [
        'identifier' => 'Pacific/Majuro',
        'offset' => '+12:00',
      ],
      19 => [
        'identifier' => 'America/Port-au-Prince',
        'offset' => '-04:00',
      ],
      20 => [
        'identifier' => 'America/Montreal',
        'offset' => '-04:00',
      ],
      21 => [
        'identifier' => 'Atlantic/Reykjavik',
        'offset' => '+00:00',
      ],
      22 => [
        'identifier' => 'America/Panama',
        'offset' => '-05:00',
      ],
      23 => [
        'identifier' => 'America/Sitka',
        'offset' => '-08:00',
      ],
      24 => [
        'identifier' => 'Asia/Ho_Chi_Minh',
        'offset' => '+07:00',
      ],
      25 => [
        'identifier' => 'America/Danmarkshavn',
        'offset' => '+00:00',
      ],
      26 => [
        'identifier' => 'Asia/Jakarta',
        'offset' => '+07:00',
      ],
      27 => [
        'identifier' => 'America/Boise',
        'offset' => '-06:00',
      ],
      28 => [
        'identifier' => 'Asia/Baghdad',
        'offset' => '+03:00',
      ],
      29 => [
        'identifier' => 'Africa/El_Aaiun',
        'offset' => '+01:00',
      ],
      30 => [
        'identifier' => 'Europe/Zagreb',
        'offset' => '+02:00',
      ],
      31 => [
        'identifier' => 'America/Santiago',
        'offset' => '-03:00',
      ],
      32 => [
        'identifier' => 'America/Merida',
        'offset' => '-05:00',
      ],
      33 => [
        'identifier' => 'Africa/Nouakchott',
        'offset' => '+00:00',
      ],
      34 => [
        'identifier' => 'America/Bahia_Banderas',
        'offset' => '-05:00',
      ],
      35 => [
        'identifier' => 'Australia/Perth',
        'offset' => '+08:00',
      ],
      36 => [
        'identifier' => 'Asia/Sakhalin',
        'offset' => '+11:00',
      ],
      37 => [
        'identifier' => 'Asia/Vladivostok',
        'offset' => '+10:00',
      ],
      38 => [
        'identifier' => 'Africa/Bissau',
        'offset' => '+00:00',
      ],
      39 => [
        'identifier' => 'America/Los_Angeles',
        'offset' => '-07:00',
      ],
      40 => [
        'identifier' => 'Asia/Rangoon',
        'offset' => '+06:30',
      ],
      41 => [
        'identifier' => 'America/Belize',
        'offset' => '-06:00',
      ],
      42 => [
        'identifier' => 'Asia/Harbin',
        'offset' => '+08:00',
      ],
      43 => [
        'identifier' => 'Australia/Currie',
        'offset' => '+10:00',
      ],
      44 => [
        'identifier' => 'Pacific/Pago_Pago',
        'offset' => '-11:00',
      ],
      45 => [
        'identifier' => 'America/Vancouver',
        'offset' => '-07:00',
      ],
      46 => [
        'identifier' => 'Asia/Magadan',
        'offset' => '+11:00',
      ],
      47 => [
        'identifier' => 'Asia/Tbilisi',
        'offset' => '+04:00',
      ],
      48 => [
        'identifier' => 'Asia/Yerevan',
        'offset' => '+04:00',
      ],
      49 => [
        'identifier' => 'Europe/Tallinn',
        'offset' => '+03:00',
      ],
      50 => [
        'identifier' => 'Pacific/Johnston',
        'offset' => '-10:00',
      ],
      51 => [
        'identifier' => 'Asia/Baku',
        'offset' => '+04:00',
      ],
      52 => [
        'identifier' => 'America/North_Dakota/New_Salem',
        'offset' => '-05:00',
      ],
      53 => [
        'identifier' => 'Europe/Vilnius',
        'offset' => '+03:00',
      ],
      54 => [
        'identifier' => 'America/Indiana/Petersburg',
        'offset' => '-04:00',
      ],
      55 => [
        'identifier' => 'Asia/Tehran',
        'offset' => '+04:30',
      ],
      56 => [
        'identifier' => 'America/Inuvik',
        'offset' => '-06:00',
      ],
      57 => [
        'identifier' => 'Europe/Lisbon',
        'offset' => '+01:00',
      ],
      58 => [
        'identifier' => 'Europe/Vatican',
        'offset' => '+02:00',
      ],
      59 => [
        'identifier' => 'Pacific/Chatham',
        'offset' => '+12:45',
      ],
      60 => [
        'identifier' => 'Antarctica/Macquarie',
        'offset' => '+11:00',
      ],
      61 => [
        'identifier' => 'America/Araguaina',
        'offset' => '-03:00',
      ],
      62 => [
        'identifier' => 'Asia/Thimphu',
        'offset' => '+06:00',
      ],
      63 => [
        'identifier' => 'Atlantic/Madeira',
        'offset' => '+01:00',
      ],
      64 => [
        'identifier' => 'America/Coral_Harbour',
        'offset' => '-05:00',
      ],
      65 => [
        'identifier' => 'Pacific/Funafuti',
        'offset' => '+12:00',
      ],
      66 => [
        'identifier' => 'Indian/Mahe',
        'offset' => '+04:00',
      ],
      67 => [
        'identifier' => 'Australia/Adelaide',
        'offset' => '+09:30',
      ],
      68 => [
        'identifier' => 'Africa/Freetown',
        'offset' => '+00:00',
      ],
      69 => [
        'identifier' => 'Atlantic/South_Georgia',
        'offset' => '-02:00',
      ],
      70 => [
        'identifier' => 'Africa/Accra',
        'offset' => '+00:00',
      ],
      71 => [
        'identifier' => 'America/North_Dakota/Beulah',
        'offset' => '-05:00',
      ],
      72 => [
        'identifier' => 'America/Jamaica',
        'offset' => '-05:00',
      ],
      73 => [
        'identifier' => 'America/Scoresbysund',
        'offset' => '+00:00',
      ],
      74 => [
        'identifier' => 'America/Swift_Current',
        'offset' => '-06:00',
      ],
      75 => [
        'identifier' => 'Europe/Tirane',
        'offset' => '+02:00',
      ],
      76 => [
        'identifier' => 'Asia/Ashgabat',
        'offset' => '+05:00',
      ],
      77 => [
        'identifier' => 'America/Moncton',
        'offset' => '-03:00',
      ],
      78 => [
        'identifier' => 'Europe/Vaduz',
        'offset' => '+02:00',
      ],
      79 => [
        'identifier' => 'Australia/Eucla',
        'offset' => '+08:45',
      ],
      80 => [
        'identifier' => 'America/Montserrat',
        'offset' => '-04:00',
      ],
      81 => [
        'identifier' => 'America/Glace_Bay',
        'offset' => '-03:00',
      ],
      82 => [
        'identifier' => 'Atlantic/Stanley',
        'offset' => '-03:00',
      ],
      83 => [
        'identifier' => 'Africa/Bujumbura',
        'offset' => '+02:00',
      ],
      84 => [
        'identifier' => 'Africa/Porto-Novo',
        'offset' => '+01:00',
      ],
      85 => [
        'identifier' => 'America/Argentina/Rio_Gallegos',
        'offset' => '-03:00',
      ],
      86 => [
        'identifier' => 'America/Grenada',
        'offset' => '-04:00',
      ],
      87 => [
        'identifier' => 'Asia/Novokuznetsk',
        'offset' => '+07:00',
      ],
      88 => [
        'identifier' => 'America/Argentina/Catamarca',
        'offset' => '-03:00',
      ],
      89 => [
        'identifier' => 'America/Indiana/Indianapolis',
        'offset' => '-04:00',
      ],
      90 => [
        'identifier' => 'America/Indiana/Tell_City',
        'offset' => '-05:00',
      ],
      91 => [
        'identifier' => 'America/Curacao',
        'offset' => '-04:00',
      ],
      92 => [
        'identifier' => 'America/Miquelon',
        'offset' => '-02:00',
      ],
      93 => [
        'identifier' => 'America/Detroit',
        'offset' => '-04:00',
      ],
      94 => [
        'identifier' => 'America/Menominee',
        'offset' => '-05:00',
      ],
      95 => [
        'identifier' => 'Asia/Novosibirsk',
        'offset' => '+07:00',
      ],
      96 => [
        'identifier' => 'Africa/Lagos',
        'offset' => '+01:00',
      ],
      97 => [
        'identifier' => 'Indian/Cocos',
        'offset' => '+06:30',
      ],
      98 => [
        'identifier' => 'America/Yakutat',
        'offset' => '-08:00',
      ],
      99 => [
        'identifier' => 'Europe/Volgograd',
        'offset' => '+03:00',
      ],
      100 => [
        'identifier' => 'Asia/Qatar',
        'offset' => '+03:00',
      ],
      101 => [
        'identifier' => 'Indian/Antananarivo',
        'offset' => '+03:00',
      ],
      102 => [
        'identifier' => 'Pacific/Marquesas',
        'offset' => '-09:30',
      ],
      103 => [
        'identifier' => 'America/Grand_Turk',
        'offset' => '-04:00',
      ],
      104 => [
        'identifier' => 'Asia/Khandyga',
        'offset' => '+09:00',
      ],
      105 => [
        'identifier' => 'America/North_Dakota/Center',
        'offset' => '-05:00',
      ],
      106 => [
        'identifier' => 'Pacific/Guam',
        'offset' => '+10:00',
      ],
      107 => [
        'identifier' => 'Pacific/Pitcairn',
        'offset' => '-08:00',
      ],
      108 => [
        'identifier' => 'America/Cambridge_Bay',
        'offset' => '-06:00',
      ],
      109 => [
        'identifier' => 'Asia/Bahrain',
        'offset' => '+03:00',
      ],
      110 => [
        'identifier' => 'America/Kentucky/Monticello',
        'offset' => '-04:00',
      ],
      111 => [
        'identifier' => 'Arctic/Longyearbyen',
        'offset' => '+02:00',
      ],
      112 => [
        'identifier' => 'Africa/Cairo',
        'offset' => '+02:00',
      ],
      113 => [
        'identifier' => 'Australia/Hobart',
        'offset' => '+10:00',
      ],
      114 => [
        'identifier' => 'Pacific/Galapagos',
        'offset' => '-06:00',
      ],
      115 => [
        'identifier' => 'Asia/Oral',
        'offset' => '+05:00',
      ],
      116 => [
        'identifier' => 'America/Dawson_Creek',
        'offset' => '-07:00',
      ],
      117 => [
        'identifier' => 'Africa/Mbabane',
        'offset' => '+02:00',
      ],
      118 => [
        'identifier' => 'America/Halifax',
        'offset' => '-03:00',
      ],
      119 => [
        'identifier' => 'Pacific/Tongatapu',
        'offset' => '+13:00',
      ],
      120 => [
        'identifier' => 'Asia/Aqtau',
        'offset' => '+05:00',
      ],
      121 => [
        'identifier' => 'Asia/Hovd',
        'offset' => '+07:00',
      ],
      122 => [
        'identifier' => 'Africa/Nairobi',
        'offset' => '+03:00',
      ],
      123 => [
        'identifier' => 'Asia/Ulaanbaatar',
        'offset' => '+08:00',
      ],
      124 => [
        'identifier' => 'Indian/Christmas',
        'offset' => '+07:00',
      ],
      125 => [
        'identifier' => 'Asia/Taipei',
        'offset' => '+08:00',
      ],
      126 => [
        'identifier' => 'Australia/Melbourne',
        'offset' => '+10:00',
      ],
      127 => [
        'identifier' => 'America/Argentina/Salta',
        'offset' => '-03:00',
      ],
      128 => [
        'identifier' => 'Australia/Broken_Hill',
        'offset' => '+09:30',
      ],
      129 => [
        'identifier' => 'America/Argentina/Tucuman',
        'offset' => '-03:00',
      ],
      130 => [
        'identifier' => 'America/Kentucky/Louisville',
        'offset' => '-04:00',
      ],
      131 => [
        'identifier' => 'Asia/Jayapura',
        'offset' => '+09:00',
      ],
      132 => [
        'identifier' => 'Asia/Macau',
        'offset' => '+08:00',
      ],
      133 => [
        'identifier' => 'America/Ojinaga',
        'offset' => '-06:00',
      ],
      134 => [
        'identifier' => 'America/Nome',
        'offset' => '-08:00',
      ],
      135 => [
        'identifier' => 'Pacific/Wake',
        'offset' => '+12:00',
      ],
      136 => [
        'identifier' => 'Europe/Andorra',
        'offset' => '+02:00',
      ],
      137 => [
        'identifier' => 'America/Iqaluit',
        'offset' => '-04:00',
      ],
      138 => [
        'identifier' => 'America/Kralendijk',
        'offset' => '-04:00',
      ],
      139 => [
        'identifier' => 'Europe/Jersey',
        'offset' => '+01:00',
      ],
      140 => [
        'identifier' => 'Asia/Ust-Nera',
        'offset' => '+10:00',
      ],
      141 => [
        'identifier' => 'Asia/Yakutsk',
        'offset' => '+09:00',
      ],
      142 => [
        'identifier' => 'America/Yellowknife',
        'offset' => '-06:00',
      ],
      143 => [
        'identifier' => 'America/Fortaleza',
        'offset' => '-03:00',
      ],
      144 => [
        'identifier' => 'Asia/Irkutsk',
        'offset' => '+08:00',
      ],
      145 => [
        'identifier' => 'America/Tegucigalpa',
        'offset' => '-06:00',
      ],
      146 => [
        'identifier' => 'Europe/Zaporozhye',
        'offset' => '+03:00',
      ],
      147 => [
        'identifier' => 'Pacific/Fiji',
        'offset' => '+12:00',
      ],
      148 => [
        'identifier' => 'Pacific/Tarawa',
        'offset' => '+12:00',
      ],
      149 => [
        'identifier' => 'Africa/Asmara',
        'offset' => '+03:00',
      ],
      150 => [
        'identifier' => 'Asia/Dhaka',
        'offset' => '+06:00',
      ],
      151 => [
        'identifier' => 'Asia/Pyongyang',
        'offset' => '+08:30',
      ],
      152 => [
        'identifier' => 'Europe/Athens',
        'offset' => '+03:00',
      ],
      153 => [
        'identifier' => 'America/Resolute',
        'offset' => '-05:00',
      ],
      154 => [
        'identifier' => 'Africa/Brazzaville',
        'offset' => '+01:00',
      ],
      155 => [
        'identifier' => 'Africa/Libreville',
        'offset' => '+01:00',
      ],
      156 => [
        'identifier' => 'Atlantic/St_Helena',
        'offset' => '+00:00',
      ],
      157 => [
        'identifier' => 'Europe/Samara',
        'offset' => '+04:00',
      ],
      158 => [
        'identifier' => 'America/Adak',
        'offset' => '-09:00',
      ],
      159 => [
        'identifier' => 'America/Argentina/Jujuy',
        'offset' => '-03:00',
      ],
      160 => [
        'identifier' => 'America/Chicago',
        'offset' => '-05:00',
      ],
      161 => [
        'identifier' => 'Africa/Sao_Tome',
        'offset' => '+00:00',
      ],
      162 => [
        'identifier' => 'Europe/Bratislava',
        'offset' => '+02:00',
      ],
      163 => [
        'identifier' => 'Asia/Riyadh',
        'offset' => '+03:00',
      ],
      164 => [
        'identifier' => 'America/Lima',
        'offset' => '-05:00',
      ],
      165 => [
        'identifier' => 'America/New_York',
        'offset' => '-04:00',
      ],
      166 => [
        'identifier' => 'America/Pangnirtung',
        'offset' => '-04:00',
      ],
      167 => [
        'identifier' => 'Asia/Samarkand',
        'offset' => '+05:00',
      ],
      168 => [
        'identifier' => 'America/Port_of_Spain',
        'offset' => '-04:00',
      ],
      169 => [
        'identifier' => 'Africa/Johannesburg',
        'offset' => '+02:00',
      ],
      170 => [
        'identifier' => 'Pacific/Port_Moresby',
        'offset' => '+10:00',
      ],
      171 => [
        'identifier' => 'America/Bahia',
        'offset' => '-03:00',
      ],
      172 => [
        'identifier' => 'Europe/Zurich',
        'offset' => '+02:00',
      ],
      173 => [
        'identifier' => 'America/St_Barthelemy',
        'offset' => '-04:00',
      ],
      174 => [
        'identifier' => 'Asia/Nicosia',
        'offset' => '+03:00',
      ],
      175 => [
        'identifier' => 'Europe/Kaliningrad',
        'offset' => '+02:00',
      ],
      176 => [
        'identifier' => 'America/Anguilla',
        'offset' => '-04:00',
      ],
      177 => [
        'identifier' => 'Europe/Ljubljana',
        'offset' => '+02:00',
      ],
      178 => [
        'identifier' => 'Asia/Yekaterinburg',
        'offset' => '+05:00',
      ],
      179 => [
        'identifier' => 'Africa/Kampala',
        'offset' => '+03:00',
      ],
      180 => [
        'identifier' => 'America/Rio_Branco',
        'offset' => '-05:00',
      ],
      181 => [
        'identifier' => 'Africa/Bamako',
        'offset' => '+00:00',
      ],
      182 => [
        'identifier' => 'America/Goose_Bay',
        'offset' => '-03:00',
      ],
      183 => [
        'identifier' => 'Europe/Moscow',
        'offset' => '+03:00',
      ],
      184 => [
        'identifier' => 'Africa/Conakry',
        'offset' => '+00:00',
      ],
      185 => [
        'identifier' => 'America/Chihuahua',
        'offset' => '-06:00',
      ],
      186 => [
        'identifier' => 'Europe/Warsaw',
        'offset' => '+02:00',
      ],
      187 => [
        'identifier' => 'Pacific/Palau',
        'offset' => '+09:00',
      ],
      188 => [
        'identifier' => 'Europe/Mariehamn',
        'offset' => '+03:00',
      ],
      189 => [
        'identifier' => 'Africa/Windhoek',
        'offset' => '+02:00',
      ],
      190 => [
        'identifier' => 'America/La_Paz',
        'offset' => '-04:00',
      ],
      191 => [
        'identifier' => 'America/Recife',
        'offset' => '-03:00',
      ],
      192 => [
        'identifier' => 'America/Mexico_City',
        'offset' => '-05:00',
      ],
      193 => [
        'identifier' => 'Asia/Amman',
        'offset' => '+03:00',
      ],
      194 => [
        'identifier' => 'America/Tijuana',
        'offset' => '-07:00',
      ],
      195 => [
        'identifier' => 'America/Metlakatla',
        'offset' => '-08:00',
      ],
      196 => [
        'identifier' => 'Pacific/Midway',
        'offset' => '-11:00',
      ],
      197 => [
        'identifier' => 'Europe/Simferopol',
        'offset' => '+03:00',
      ],
      198 => [
        'identifier' => 'Europe/Budapest',
        'offset' => '+02:00',
      ],
      199 => [
        'identifier' => 'Pacific/Apia',
        'offset' => '+13:00',
      ],
      200 => [
        'identifier' => 'America/Paramaribo',
        'offset' => '-03:00',
      ],
      201 => [
        'identifier' => 'Africa/Malabo',
        'offset' => '+01:00',
      ],
      202 => [
        'identifier' => 'Africa/Ndjamena',
        'offset' => '+01:00',
      ],
      203 => [
        'identifier' => 'Asia/Choibalsan',
        'offset' => '+08:00',
      ],
      204 => [
        'identifier' => 'America/Antigua',
        'offset' => '-04:00',
      ],
      205 => [
        'identifier' => 'Europe/Istanbul',
        'offset' => '+03:00',
      ],
      206 => [
        'identifier' => 'Africa/Blantyre',
        'offset' => '+02:00',
      ],
      207 => [
        'identifier' => 'Australia/Sydney',
        'offset' => '+10:00',
      ],
      208 => [
        'identifier' => 'Asia/Dushanbe',
        'offset' => '+05:00',
      ],
      209 => [
        'identifier' => 'Europe/Belgrade',
        'offset' => '+02:00',
      ],
      210 => [
        'identifier' => 'Asia/Karachi',
        'offset' => '+05:00',
      ],
      211 => [
        'identifier' => 'Europe/Luxembourg',
        'offset' => '+02:00',
      ],
      212 => [
        'identifier' => 'Europe/Podgorica',
        'offset' => '+02:00',
      ],
      213 => [
        'identifier' => 'Australia/Lindeman',
        'offset' => '+10:00',
      ],
      214 => [
        'identifier' => 'Africa/Bangui',
        'offset' => '+01:00',
      ],
      215 => [
        'identifier' => 'Asia/Aden',
        'offset' => '+03:00',
      ],
      216 => [
        'identifier' => 'Pacific/Chuuk',
        'offset' => '+10:00',
      ],
      217 => [
        'identifier' => 'Asia/Brunei',
        'offset' => '+08:00',
      ],
      218 => [
        'identifier' => 'Indian/Comoro',
        'offset' => '+03:00',
      ],
      219 => [
        'identifier' => 'America/Asuncion',
        'offset' => '-04:00',
      ],
      220 => [
        'identifier' => 'Europe/Prague',
        'offset' => '+02:00',
      ],
      221 => [
        'identifier' => 'America/Cayman',
        'offset' => '-05:00',
      ],
      222 => [
        'identifier' => 'Pacific/Pohnpei',
        'offset' => '+11:00',
      ],
      223 => [
        'identifier' => 'America/Atikokan',
        'offset' => '-05:00',
      ],
      224 => [
        'identifier' => 'Pacific/Norfolk',
        'offset' => '+11:00',
      ],
      225 => [
        'identifier' => 'Africa/Dakar',
        'offset' => '+00:00',
      ],
      226 => [
        'identifier' => 'America/Argentina/Buenos_Aires',
        'offset' => '-03:00',
      ],
      227 => [
        'identifier' => 'America/Edmonton',
        'offset' => '-06:00',
      ],
      228 => [
        'identifier' => 'America/Barbados',
        'offset' => '-04:00',
      ],
      229 => [
        'identifier' => 'America/Santo_Domingo',
        'offset' => '-04:00',
      ],
      230 => [
        'identifier' => 'Asia/Bishkek',
        'offset' => '+06:00',
      ],
      231 => [
        'identifier' => 'Asia/Kuwait',
        'offset' => '+03:00',
      ],
      232 => [
        'identifier' => 'Pacific/Efate',
        'offset' => '+11:00',
      ],
      233 => [
        'identifier' => 'Indian/Mauritius',
        'offset' => '+04:00',
      ],
      234 => [
        'identifier' => 'America/Aruba',
        'offset' => '-04:00',
      ],
      235 => [
        'identifier' => 'Australia/Brisbane',
        'offset' => '+10:00',
      ],
      236 => [
        'identifier' => 'Indian/Kerguelen',
        'offset' => '+05:00',
      ],
      237 => [
        'identifier' => 'Pacific/Kiritimati',
        'offset' => '+14:00',
      ],
      238 => [
        'identifier' => 'America/Toronto',
        'offset' => '-04:00',
      ],
      239 => [
        'identifier' => 'Asia/Qyzylorda',
        'offset' => '+06:00',
      ],
      240 => [
        'identifier' => 'Asia/Aqtobe',
        'offset' => '+05:00',
      ],
      241 => [
        'identifier' => 'America/Eirunepe',
        'offset' => '-05:00',
      ],
      242 => [
        'identifier' => 'Europe/Isle_of_Man',
        'offset' => '+01:00',
      ],
      243 => [
        'identifier' => 'America/Blanc-Sablon',
        'offset' => '-04:00',
      ],
      244 => [
        'identifier' => 'Pacific/Honolulu',
        'offset' => '-10:00',
      ],
      245 => [
        'identifier' => 'America/Montevideo',
        'offset' => '-03:00',
      ],
      246 => [
        'identifier' => 'Asia/Tashkent',
        'offset' => '+05:00',
      ],
      247 => [
        'identifier' => 'Pacific/Kosrae',
        'offset' => '+11:00',
      ],
      248 => [
        'identifier' => 'America/Indiana/Winamac',
        'offset' => '-04:00',
      ],
      249 => [
        'identifier' => 'America/Argentina/La_Rioja',
        'offset' => '-03:00',
      ],
      250 => [
        'identifier' => 'Africa/Mogadishu',
        'offset' => '+03:00',
      ],
      251 => [
        'identifier' => 'Asia/Phnom_Penh',
        'offset' => '+07:00',
      ],
      252 => [
        'identifier' => 'Africa/Banjul',
        'offset' => '+00:00',
      ],
      253 => [
        'identifier' => 'America/Creston',
        'offset' => '-07:00',
      ],
      254 => [
        'identifier' => 'Europe/Brussels',
        'offset' => '+02:00',
      ],
      255 => [
        'identifier' => 'Asia/Gaza',
        'offset' => '+03:00',
      ],
      256 => [
        'identifier' => 'Atlantic/Bermuda',
        'offset' => '-03:00',
      ],
      257 => [
        'identifier' => 'America/Indiana/Knox',
        'offset' => '-05:00',
      ],
      258 => [
        'identifier' => 'America/El_Salvador',
        'offset' => '-06:00',
      ],
      259 => [
        'identifier' => 'America/Managua',
        'offset' => '-06:00',
      ],
      260 => [
        'identifier' => 'Africa/Niamey',
        'offset' => '+01:00',
      ],
      261 => [
        'identifier' => 'Europe/Monaco',
        'offset' => '+02:00',
      ],
      262 => [
        'identifier' => 'Africa/Ouagadougou',
        'offset' => '+00:00',
      ],
      263 => [
        'identifier' => 'Pacific/Easter',
        'offset' => '-05:00',
      ],
      264 => [
        'identifier' => 'Atlantic/Canary',
        'offset' => '+01:00',
      ],
      265 => [
        'identifier' => 'Asia/Vientiane',
        'offset' => '+07:00',
      ],
      266 => [
        'identifier' => 'Europe/Bucharest',
        'offset' => '+03:00',
      ],
      267 => [
        'identifier' => 'Africa/Lusaka',
        'offset' => '+02:00',
      ],
      268 => [
        'identifier' => 'Asia/Kathmandu',
        'offset' => '+05:45',
      ],
      269 => [
        'identifier' => 'Africa/Harare',
        'offset' => '+02:00',
      ],
      270 => [
        'identifier' => 'Asia/Bangkok',
        'offset' => '+07:00',
      ],
      271 => [
        'identifier' => 'Europe/Rome',
        'offset' => '+02:00',
      ],
      272 => [
        'identifier' => 'Africa/Lome',
        'offset' => '+00:00',
      ],
      273 => [
        'identifier' => 'America/Denver',
        'offset' => '-06:00',
      ],
      274 => [
        'identifier' => 'Indian/Reunion',
        'offset' => '+04:00',
      ],
      275 => [
        'identifier' => 'Europe/Kiev',
        'offset' => '+03:00',
      ],
      276 => [
        'identifier' => 'Europe/Vienna',
        'offset' => '+02:00',
      ],
      277 => [
        'identifier' => 'America/Guadeloupe',
        'offset' => '-04:00',
      ],
      278 => [
        'identifier' => 'America/Argentina/Cordoba',
        'offset' => '-03:00',
      ],
      279 => [
        'identifier' => 'Asia/Manila',
        'offset' => '+08:00',
      ],
      280 => [
        'identifier' => 'Asia/Tokyo',
        'offset' => '+09:00',
      ],
      281 => [
        'identifier' => 'America/Nassau',
        'offset' => '-04:00',
      ],
      282 => [
        'identifier' => 'Pacific/Enderbury',
        'offset' => '+13:00',
      ],
      283 => [
        'identifier' => 'Atlantic/Azores',
        'offset' => '+00:00',
      ],
      284 => [
        'identifier' => 'America/Winnipeg',
        'offset' => '-05:00',
      ],
      285 => [
        'identifier' => 'Europe/Dublin',
        'offset' => '+01:00',
      ],
      286 => [
        'identifier' => 'Asia/Kuching',
        'offset' => '+08:00',
      ],
      287 => [
        'identifier' => 'America/Argentina/Ushuaia',
        'offset' => '-03:00',
      ],
      288 => [
        'identifier' => 'Asia/Colombo',
        'offset' => '+05:30',
      ],
      289 => [
        'identifier' => 'Asia/Krasnoyarsk',
        'offset' => '+07:00',
      ],
      290 => [
        'identifier' => 'America/St_Johns',
        'offset' => '-02:30',
      ],
      291 => [
        'identifier' => 'Asia/Shanghai',
        'offset' => '+08:00',
      ],
      292 => [
        'identifier' => 'Pacific/Kwajalein',
        'offset' => '+12:00',
      ],
      293 => [
        'identifier' => 'Africa/Kigali',
        'offset' => '+02:00',
      ],
      294 => [
        'identifier' => 'Europe/Chisinau',
        'offset' => '+03:00',
      ],
      295 => [
        'identifier' => 'America/Noronha',
        'offset' => '-02:00',
      ],
      296 => [
        'identifier' => 'Europe/Guernsey',
        'offset' => '+01:00',
      ],
      297 => [
        'identifier' => 'Europe/Paris',
        'offset' => '+02:00',
      ],
      298 => [
        'identifier' => 'America/Guyana',
        'offset' => '-04:00',
      ],
      299 => [
        'identifier' => 'Africa/Luanda',
        'offset' => '+01:00',
      ],
      300 => [
        'identifier' => 'Africa/Abidjan',
        'offset' => '+00:00',
      ],
      301 => [
        'identifier' => 'America/Tortola',
        'offset' => '-04:00',
      ],
      302 => [
        'identifier' => 'Europe/Malta',
        'offset' => '+02:00',
      ],
      303 => [
        'identifier' => 'Europe/London',
        'offset' => '+01:00',
      ],
      304 => [
        'identifier' => 'Pacific/Guadalcanal',
        'offset' => '+11:00',
      ],
      305 => [
        'identifier' => 'Pacific/Gambier',
        'offset' => '-09:00',
      ],
      306 => [
        'identifier' => 'America/Thule',
        'offset' => '-03:00',
      ],
      307 => [
        'identifier' => 'America/Rankin_Inlet',
        'offset' => '-05:00',
      ],
      308 => [
        'identifier' => 'America/Regina',
        'offset' => '-06:00',
      ],
      309 => [
        'identifier' => 'America/Indiana/Vincennes',
        'offset' => '-04:00',
      ],
      310 => [
        'identifier' => 'America/Santarem',
        'offset' => '-03:00',
      ],
      311 => [
        'identifier' => 'Africa/Djibouti',
        'offset' => '+03:00',
      ],
      312 => [
        'identifier' => 'Pacific/Tahiti',
        'offset' => '-10:00',
      ],
      313 => [
        'identifier' => 'Europe/San_Marino',
        'offset' => '+02:00',
      ],
      314 => [
        'identifier' => 'America/Argentina/San_Luis',
        'offset' => '-03:00',
      ],
      315 => [
        'identifier' => 'Africa/Ceuta',
        'offset' => '+02:00',
      ],
      316 => [
        'identifier' => 'Asia/Singapore',
        'offset' => '+08:00',
      ],
      317 => [
        'identifier' => 'America/Campo_Grande',
        'offset' => '-04:00',
      ],
      318 => [
        'identifier' => 'Africa/Tunis',
        'offset' => '+01:00',
      ],
      319 => [
        'identifier' => 'Europe/Copenhagen',
        'offset' => '+02:00',
      ],
      320 => [
        'identifier' => 'Asia/Pontianak',
        'offset' => '+07:00',
      ],
      321 => [
        'identifier' => 'Asia/Dubai',
        'offset' => '+04:00',
      ],
      322 => [
        'identifier' => 'Africa/Khartoum',
        'offset' => '+02:00',
      ],
      323 => [
        'identifier' => 'Europe/Helsinki',
        'offset' => '+03:00',
      ],
      324 => [
        'identifier' => 'America/Whitehorse',
        'offset' => '-07:00',
      ],
      325 => [
        'identifier' => 'America/Maceio',
        'offset' => '-03:00',
      ],
      326 => [
        'identifier' => 'Africa/Douala',
        'offset' => '+01:00',
      ],
      327 => [
        'identifier' => 'Asia/Kuala_Lumpur',
        'offset' => '+08:00',
      ],
      328 => [
        'identifier' => 'America/Martinique',
        'offset' => '-04:00',
      ],
      329 => [
        'identifier' => 'America/Sao_Paulo',
        'offset' => '-03:00',
      ],
      330 => [
        'identifier' => 'America/Dawson',
        'offset' => '-07:00',
      ],
      331 => [
        'identifier' => 'Africa/Kinshasa',
        'offset' => '+01:00',
      ],
      332 => [
        'identifier' => 'Europe/Riga',
        'offset' => '+03:00',
      ],
      333 => [
        'identifier' => 'Africa/Tripoli',
        'offset' => '+02:00',
      ],
      334 => [
        'identifier' => 'Europe/Madrid',
        'offset' => '+02:00',
      ],
      335 => [
        'identifier' => 'America/Nipigon',
        'offset' => '-04:00',
      ],
      336 => [
        'identifier' => 'Pacific/Fakaofo',
        'offset' => '+13:00',
      ],
      337 => [
        'identifier' => 'Europe/Skopje',
        'offset' => '+02:00',
      ],
      338 => [
        'identifier' => 'America/St_Thomas',
        'offset' => '-04:00',
      ],
      339 => [
        'identifier' => 'Africa/Maseru',
        'offset' => '+02:00',
      ],
      340 => [
        'identifier' => 'Europe/Sofia',
        'offset' => '+03:00',
      ],
      341 => [
        'identifier' => 'America/Porto_Velho',
        'offset' => '-04:00',
      ],
      342 => [
        'identifier' => 'America/St_Kitts',
        'offset' => '-04:00',
      ],
      343 => [
        'identifier' => 'Africa/Casablanca',
        'offset' => '+01:00',
      ],
      344 => [
        'identifier' => 'Asia/Hebron',
        'offset' => '+03:00',
      ],
      345 => [
        'identifier' => 'Asia/Dili',
        'offset' => '+09:00',
      ],
      346 => [
        'identifier' => 'America/Argentina/San_Juan',
        'offset' => '-03:00',
      ],
      347 => [
        'identifier' => 'Asia/Almaty',
        'offset' => '+06:00',
      ],
      348 => [
        'identifier' => 'Europe/Sarajevo',
        'offset' => '+02:00',
      ],
      349 => [
        'identifier' => 'America/Boa_Vista',
        'offset' => '-04:00',
      ],
      350 => [
        'identifier' => 'Africa/Addis_Ababa',
        'offset' => '+03:00',
      ],
      351 => [
        'identifier' => 'Indian/Mayotte',
        'offset' => '+03:00',
      ],
      352 => [
        'identifier' => 'Africa/Lubumbashi',
        'offset' => '+02:00',
      ],
      353 => [
        'identifier' => 'Atlantic/Cape_Verde',
        'offset' => '-01:00',
      ],
      354 => [
        'identifier' => 'America/Lower_Princes',
        'offset' => '-04:00',
      ],
      355 => [
        'identifier' => 'Europe/Oslo',
        'offset' => '+02:00',
      ],
      356 => [
        'identifier' => 'Africa/Monrovia',
        'offset' => '+00:00',
      ],
      357 => [
        'identifier' => 'Asia/Muscat',
        'offset' => '+04:00',
      ],
      358 => [
        'identifier' => 'America/Thunder_Bay',
        'offset' => '-04:00',
      ],
      359 => [
        'identifier' => 'America/Juneau',
        'offset' => '-08:00',
      ],
      360 => [
        'identifier' => 'Pacific/Rarotonga',
        'offset' => '-10:00',
      ],
      361 => [
        'identifier' => 'Atlantic/Faroe',
        'offset' => '+01:00',
      ],
      362 => [
        'identifier' => 'America/Cayenne',
        'offset' => '-03:00',
      ],
      363 => [
        'identifier' => 'America/Cuiaba',
        'offset' => '-04:00',
      ],
      364 => [
        'identifier' => 'Africa/Maputo',
        'offset' => '+02:00',
      ],
      365 => [
        'identifier' => 'Asia/Anadyr',
        'offset' => '+12:00',
      ],
      366 => [
        'identifier' => 'Asia/Kabul',
        'offset' => '+04:30',
      ],
      367 => [
        'identifier' => 'America/Santa_Isabel',
        'offset' => '-07:00',
      ],
      368 => [
        'identifier' => 'Asia/Damascus',
        'offset' => '+03:00',
      ],
      369 => [
        'identifier' => 'Pacific/Noumea',
        'offset' => '+11:00',
      ],
      370 => [
        'identifier' => 'America/Anchorage',
        'offset' => '-08:00',
      ],
      371 => [
        'identifier' => 'Asia/Kolkata',
        'offset' => '+05:30',
      ],
      372 => [
        'identifier' => 'Pacific/Niue',
        'offset' => '-11:00',
      ],
      373 => [
        'identifier' => 'Asia/Kamchatka',
        'offset' => '+12:00',
      ],
      374 => [
        'identifier' => 'America/Matamoros',
        'offset' => '-05:00',
      ],
      375 => [
        'identifier' => 'Europe/Stockholm',
        'offset' => '+02:00',
      ],
      376 => [
        'identifier' => 'America/Havana',
        'offset' => '-04:00',
      ],
      377 => [
        'identifier' => 'Pacific/Auckland',
        'offset' => '+12:00',
      ],
      378 => [
        'identifier' => 'America/Rainy_River',
        'offset' => '-05:00',
      ],
      379 => [
        'identifier' => 'Asia/Omsk',
        'offset' => '+06:00',
      ],
      380 => [
        'identifier' => 'Africa/Algiers',
        'offset' => '+01:00',
      ],
      381 => [
        'identifier' => 'America/Guayaquil',
        'offset' => '-05:00',
      ],
      382 => [
        'identifier' => 'Indian/Maldives',
        'offset' => '+05:00',
      ],
      383 => [
        'identifier' => 'Asia/Makassar',
        'offset' => '+08:00',
      ],
      384 => [
        'identifier' => 'America/Monterrey',
        'offset' => '-05:00',
      ],
      385 => [
        'identifier' => 'Europe/Amsterdam',
        'offset' => '+02:00',
      ],
      386 => [
        'identifier' => 'America/St_Lucia',
        'offset' => '-04:00',
      ],
      387 => [
        'identifier' => 'Europe/Uzhgorod',
        'offset' => '+03:00',
      ],
      388 => [
        'identifier' => 'America/Indiana/Marengo',
        'offset' => '-04:00',
      ],
      389 => [
        'identifier' => 'Pacific/Saipan',
        'offset' => '+10:00',
      ],
      390 => [
        'identifier' => 'America/Bogota',
        'offset' => '-05:00',
      ],
      391 => [
        'identifier' => 'America/Indiana/Vevay',
        'offset' => '-04:00',
      ],
      392 => [
        'identifier' => 'America/Guatemala',
        'offset' => '-06:00',
      ],
      393 => [
        'identifier' => 'America/Puerto_Rico',
        'offset' => '-04:00',
      ],
      394 => [
        'identifier' => 'America/Marigot',
        'offset' => '-04:00',
      ],
      395 => [
        'identifier' => 'Africa/Juba',
        'offset' => '+03:00',
      ],
      396 => [
        'identifier' => 'America/Costa_Rica',
        'offset' => '-06:00',
      ],
      397 => [
        'identifier' => 'America/Caracas',
        'offset' => '-04:00',
      ],
      398 => [
        'identifier' => 'Pacific/Nauru',
        'offset' => '+12:00',
      ],
      399 => [
        'identifier' => 'Europe/Minsk',
        'offset' => '+03:00',
      ],
      400 => [
        'identifier' => 'America/Belem',
        'offset' => '-03:00',
      ],
      401 => [
        'identifier' => 'America/Cancun',
        'offset' => '-05:00',
      ],
      402 => [
        'identifier' => 'America/Hermosillo',
        'offset' => '-07:00',
      ],
      403 => [
        'identifier' => 'Asia/Chongqing',
        'offset' => '+08:00',
      ],
      404 => [
        'identifier' => 'Asia/Beirut',
        'offset' => '+03:00',
      ],
      405 => [
        'identifier' => 'Europe/Gibraltar',
        'offset' => '+02:00',
      ],
      406 => [
        'identifier' => 'Asia/Urumqi',
        'offset' => '+06:00',
      ],
      407 => [
        'identifier' => 'America/Mazatlan',
        'offset' => '-06:00',
      ],
    ];
  }
}
