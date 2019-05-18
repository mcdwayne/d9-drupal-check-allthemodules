<?php

namespace Drupal\flags\Flags;

use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides list of countries.
 */
class FlagsManager implements FlagsManagerInterface {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * An array of country code => country name pairs.
   */
  protected $flags;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * Get an array of all two-letter country code => country name pairs.
   *
   * @return array
   *   An array of country code => country name pairs.
   */
  public function getStandardList() {
    $flags = [
      'ac' => t('Ascension Island'),
      'ad' => t('Andorra'),
      'ae' => t('United Arab Emirates'),
      'af' => t('Afghanistan'),
      'ag' => t('Antigua and Barbuda'),
      'ai' => t('Anguilla'),
      'al' => t('Albania'),
      'am' => t('Armenia'),
      'an' => t('Netherlands Antilles'),
      'ao' => t('Angola'),
      'aq' => t('Antarctica'),
      'ar' => t('Argentina'),
      'ara' => t('Arabic'),
      'as' => t('American Samoa'),
      'at' => t('Austria'),
      'au' => t('Australia'),
      'aw' => t('Aruba'),
      'ax' => t('Åland'),
      'az' => t('Azerbaijan'),
      'ba' => t('Bosnia and Herzegovina'),
      'bb' => t('Barbados'),
      'bd' => t('Bangladesh'),
      'be' => t('Belgium'),
      'bf' => t('Burkina Faso'),
      'bg' => t('Bulgaria'),
      'bh' => t('Bahrain'),
      'bi' => t('Burundi'),
      'bj' => t('Benin'),
      'bl' => t('Saint Barthelemy'),
      'bm' => t('Bermuda'),
      'bn' => t('Brunei'),
      'bo' => t('Bolivia'),
      'bod' => t('Tibet'),
      'br' => t('Brazil'),
      'bs' => t('Bahamas'),
      'bt' => t('Bhutan'),
      'bw' => t('Botswana'),
      'by' => t('Belarus'),
      'bz' => t('Belize'),
      'ca' => t('Canada'),
      'cc' => t('Cocos (Keeling) Islands'),
      'cd' => t('Congo, Democratic Republic of'),
      'cf' => t('Central African Republic'),
      'cg' => t('Congo, Republic of'),
      'ch' => t('Switzerland'),
      'ci' => t('Ivory Coast'),
      'ck' => t('Cook Islands'),
      'cl' => t('Chile'),
      'cm' => t('Cameroon'),
      'cn' => t('China'),
      'co' => t('Colombia'),
      'cr' => t('Costa Rica'),
      'crs-io' => t('Chagossians'),
      'cu' => t('Cuba'),
      'cv' => t('Cape Verde'),
      'cw' => t('Curaçao'),
      'cx' => t('Christmas Island'),
      'cy' => t('Cyprus'),
      'cz' => t('Czech Republic'),
      'de' => t('Germany'),
      'dj' => t('Djibouti'),
      'dk' => t('Denmark'),
      'dm' => t('Dominica'),
      'do' => t('Dominican Republic'),
      'dz' => t('Algeria'),
      'ec' => t('Ecuador'),
      'ee' => t('Estonia'),
      'eg' => t('Egypt'),
      'eh' => t('Western Sahara'),
      'epo' => t('Esperanto'),
      'er' => t('Eritrea'),
      'es' => t('Spain'),
      'es-as' => t('Asturias'),
      'es-ct' => t('Catalonia'),
      'es-ga' => t('Galicia'),
      'eu' => t('Europe'),
      'eus' => t('Basqueland'),
      'et' => t('Ethiopia'),
      'fi' => t('Finland'),
      'fj' => t('Fiji'),
      'fk' => t('Falkland Islands'),
      'fm' => t('Federated States of Micronesia'),
      'fo' => t('Faroe Islands'),
      'fr' => t('France'),
      'ga' => t('Gabon'),
      'gb' => t('United Kingdom'),
      'gb-eng' => t('England'),
      'gb-sct' => t('Scotland'),
      'gb-wls' => t('Wales'),
      'gd' => t('Grenada'),
      'ge' => t('Georgia'),
      'gf' => t('French Guiana'),
      'gg' => t('Guernsey'),
      'gh' => t('Ghana'),
      'gi' => t('Gibraltar'),
      'gl' => t('Greenland'),
      'gm' => t('Gambia'),
      'gn' => t('Guinea'),
      'gp' => t('Guadeloupe'),
      'gq' => t('Equatorial Guinea'),
      'gr' => t('Greece'),
      'gs' => t('South Georgia and the South Sandwich Islands'),
      'gt' => t('Guatemala'),
      'gu' => t('Guam'),
      'gw' => t('Guinea-Bissau'),
      'gy' => t('Guyana'),
      'hk' => t('Hong Kong'),
      'hn' => t('Honduras'),
      'hr' => t('Croatia'),
      'ht' => t('Haiti'),
      'hu' => t('Hungary'),
      'id' => t('Indonesia'),
      'ie' => t('Ireland'),
      'il' => t('Israel'),
      'im' => t('Isle of Man'),
      'in' => t('India'),
      'io' => t('British Indian Ocean Territory'),
      'iq' => t('Iraq'),
      'ir' => t('Iran'),
      'is' => t('Iceland'),
      'it' => t('Italy'),
      'je' => t('Jersey'),
      'jm' => t('Jamaica'),
      'jo' => t('Jordan'),
      'jp' => t('Japan'),
      'ke' => t('Kenya'),
      'kg' => t('Kyrgyzstan'),
      'kh' => t('Cambodia'),
      'ki' => t('Kiribati'),
      'km' => t('Comoros'),
      'kn' => t('Saint Kitts and Nevis'),
      'kp' => t('North Korea'),
      'kr' => t('South Korea'),
      'kur' => t('Kurdistan'),
      'kw' => t('Kuwait'),
      'ky' => t('Cayman Islands'),
      'kz' => t('Kazakhstan'),
      'la' => t('Laos'),
      'lb' => t('Lebanon'),
      'lc' => t('Saint Lucia'),
      'li' => t('Liechtenstein'),
      'lk' => t('Sri Lanka'),
      'lr' => t('Liberia'),
      'ls' => t('Lesotho'),
      'lt' => t('Lithuania'),
      'lu' => t('Luxembourg'),
      'lv' => t('Latvia'),
      'ly' => t('Libya'),
      'ma' => t('Morocco'),
      'mc' => t('Monaco'),
      'md' => t('Moldova'),
      'me' => t('Montenegro'),
      'mg' => t('Madagascar'),
      'mh' => t('Marshall Islands'),
      'mk' => t('Macedonia'),
      'ml' => t('Mali'),
      'mm' => t('Myanmar / Burma'),
      'mn' => t('Mongolia'),
      'mo' => t('Macau'),
      'mp' => t('Northern Mariana Islands'),
      'mq' => t('Martinique'),
      'mr' => t('Mauritania'),
      'ms' => t('Montserrat'),
      'mt' => t('Malta'),
      'mu' => t('Mauritius'),
      'mv' => t('Maldives'),
      'mw' => t('Malawi'),
      'mx' => t('Mexico'),
      'my' => t('Malaysia'),
      'mz' => t('Mozambique'),
      'na' => t('Namibia'),
      'nc' => t('New Caledonia'),
      'ne' => t('Niger'),
      'nf' => t('Norfolk Island'),
      'ng' => t('Nigeria'),
      'ni' => t('Nicaragua'),
      'nl' => t('Netherlands'),
      'nl-fy' => t('Friesland'),
      'no' => t('Norway'),
      'np' => t('Nepal'),
      'nr' => t('Nauru'),
      'nu' => t('Niue'),
      'nz' => t('New Zealand'),
      'oci' => t('Occitania'),
      'om' => t('Oman'),
      'pa' => t('Panama'),
      'pe' => t('Peru'),
      'pf' => t('French Polynesia'),
      'pg' => t('Papua New Guinea'),
      'ph' => t('Philippines'),
      'pk' => t('Pakistan'),
      'pl' => t('Poland'),
      'pm' => t('Saint Pierre and Miquelon'),
      'pn' => t('Pitcairn Islands'),
      'pr' => t('Puerto Rico'),
      'ps' => t('Saint Lucia'),
      'pt' => t('Portugal'),
      'pw' => t('Palau'),
      'py' => t('Paraguay'),
      'qa' => t('Qatar'),
      'ro' => t('Romania'),
      'rs' => t('Serbia'),
      'ru' => t('Russia'),
      'ru-ty' => t('Tuvalu'),
      'rw' => t('Rwanda'),
      'sa' => t('Saudi Arabia'),
      'sb' => t('Solomon Islands'),
      'sc' => t('Seychelles'),
      'sd' => t('Sudan'),
      'se' => t('Sweden'),
      'sg' => t('Singapore'),
      'sh' => t('Saint Helena'),
      'si' => t('Slovenia'),
      'sk' => t('Slovakia'),
      'sl' => t('Sierra Leone'),
      'sm' => t('San Marino'),
      'sme' => t('Sami'),
      'sn' => t('Senegal'),
      'so' => t('Somalia'),
      'sr' => t('Suriname'),
      'ss' => t('South Sudan'),
      'st' => t('São Tomé and Príncipe'),
      'sv' => t('El Salvador'),
      'sx' => t('Sint Maarten'),
      'sy' => t('Syria'),
      'sz' => t('Swaziland'),
      'ta' => t('Tristan da Cunha'),
      'tam' => t('Tamil'),
      'tc' => t('Turks and Caicos Islands'),
      'td' => t('Chad'),
      'tf' => t('French Southern and Antarctic Lands'),
      'tg' => t('Togo'),
      'th' => t('Thailand'),
      'tj' => t('Tajikistan'),
      'tk' => t('Tokelau'),
      'tl' => t('East Timor'),
      'tm' => t('Turkmenistan'),
      'tn' => t('Tunisia'),
      'to' => t('Tonga'),
      'tr' => t('Turkey'),
      'tt' => t('Trinidad and Tobago'),
      'tv' => t('Tuvalu'),
      'tw' => t('Taiwan'),
      'tz' => t('Tanzania'),
      'ua' => t('Ukraine'),
      'ug' => t('Uganda'),
      'uig' => t('East Turkestan'),
      'un' => t('United Nations'),
      'us' => t('United States'),
      'uy' => t('Uruguay'),
      'uz' => t('Uzbekistan'),
      'va' => t('Vatican City'),
      'vc' => t('Saint Vincent and the Grenadines'),
      've' => t('Venezuela'),
      'vg' => t('British Virgin Islands'),
      'vi' => t('United States Virgin Islands'),
      'vn' => t('Vietnam'),
      'vu' => t('Vanuatu'),
      'wf' => t('Wallis and Futuna'),
      'ws' => t('Samoa'),
      'xk' => t('Kosovo'),
      'ye' => t('Yemen'),
      'yt' => t('Mayotte'),
      'za' => t('South Africa'),
      'zm' => t('Zambia'),
      'zw' => t('Zimbabwe'),
    ];

    // Sort the list.
    natcasesort($flags);

    return $flags;
  }

  /**
   * Get an array of country code => country name pairs, altered by alter hooks.
   *
   * @return array
   *   An array of country code => country name pairs.
   *
   * @see \Drupal\Core\Locale\CountryManager::getStandardList()
   */
  public function getList() {
    // Populate the country list if it is not already populated.
    if (!isset($this->flags)) {
      $this->flags = static::getStandardList();
      $this->moduleHandler->alter('flags', $this->flags);
    }

    return $this->flags;
  }

}
