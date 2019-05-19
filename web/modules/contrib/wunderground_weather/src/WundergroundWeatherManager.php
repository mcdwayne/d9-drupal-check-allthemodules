<?php
/**
 * @file
 * Contains Drupal\wunderground_weather\WundergroundWeatherManager.
 */

namespace Drupal\wunderground_weather;


use Drupal\Core\Config\ConfigFactory;
use GuzzleHttp\Client;
/**
 * Methods to make an API call and tool to handle the output.
 */
class WundergroundWeatherManager {
  /**
   * Defines the configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  private $configFactory;
  /**
   * An client to make http requests.
   *
   * @var \GuzzleHttp\Client
   */
  private $httpClient;

  /**
   * WundergroundWeatherTools constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   Defines the configuration object factory.
   * @param \GuzzleHttp\Client $http_client
   *   An client to make http requests.
   */
  public function __construct(ConfigFactory $config_factory, Client $http_client) {
    $this->configFactory = $config_factory;
    $this->httpClient = $http_client;
  }

  /**
   * Get the module settings.
   *
   * @return \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   *   The configuration object.
   */
  public function getSettings() {
    return $this->configFactory->get('wunderground_weather.settings');
  }

  /**
   * Make a request to the ww server and return it as an array.
   *
   * @param array $options
   *   Options build the request url.
   *
   * @return array
   *   An array containing weather data.
   */
  public function requestData($options) {
    $url = 'http://api.wunderground.com';
    foreach ($options as $argument) {
      $url .= '/' . $argument;
    }
    $url .= '.json';

    $data = $this->httpClient->request('GET', $url);
    return json_decode($data->getBody());
  }

  /**
   * Transform the url to user an other icon set.
   *
   * @param string $set
   *   The letter to identify an icon set.
   * @param string $icon
   *   The name of the icon.
   *
   * @return string
   *   Url of the selected icon set.
   */
  public static function getIconUrl($set, $icon) {
    $path = drupal_get_path('module', 'wunderground_weather');
    return $path . '/icons/' . $set . '/' . $icon . '.gif';
  }

  /**
   * Get a sample of icons of a icon set.
   *
   * @param string $set
   *   The letter to identify an icon set.
   *
   * @return string
   *   A div containing a sample of icons from an icon set.
   */
  public function getIconSetSample($set) {
    $all_icons = $this->getIconNames();
    $sample = [
      $all_icons[8],
      $all_icons[9],
      $all_icons[15],
      $all_icons[18],
      $all_icons[20],
    ];

    $sample_icons = '';
    foreach ($sample as $name) {
      $image_variables = [
        '#theme' => 'image',
        '#uri' => $this->getIconUrl($set, $name),
      ];
      $sample_icons .= render($image_variables);
    };

    return $sample_icons;
  }

  /**
   * Get all available icon names.
   *
   * @return array
   *   All available icon names.
   */
  public function getIconNames() {
    return [
      'chanceflurries',
      'chancerain',
      'chancerain',
      'chancesleet',
      'chancesleet',
      'chancesnow',
      'chancetstorms',
      'chancetstorms',
      'clear',
      'cloudy',
      'flurries',
      'fog',
      'hazy',
      'mostlycloudy',
      'mostlysunny',
      'partlycloudy',
      'partlysunny',
      'sleet',
      'rain',
      'sleet',
      'snow',
      'sunny',
      'tstorms',
      'tstorms',
      'unknown',
      'cloudy',
      'partlycloudy',
    ];
  }

  /**
   * Convert wind speed to beaufort.
   *
   * @param int $speed
   *   Windspeed in kp/h or m/h.
   * @param string $unit
   *   Windspeed unit.
   *
   * @return int
   *   Windspeed in Bft.
   */
  public function windSpeedToBeaufort($speed, $unit) {
    $speed = $unit == 'kph' ? $speed : $speed * 1.6;

    switch (TRUE) {
      case ($speed < 1):
        $bft = 0;
        break;

      case ($speed < 5.6):
        $bft = 1;
        break;

      case ($speed < 12):
        $bft = 2;
        break;

      case ($speed < 20):
        $bft = 3;
        break;

      case ($speed < 29):
        $bft = 4;
        break;

      case ($speed < 39):
        $bft = 5;
        break;

      case ($speed < 50):
        $bft = 6;
        break;

      case ($speed < 62):
        $bft = 7;
        break;

      case ($speed < 75):
        $bft = 8;
        break;

      case ($speed < 89):
        $bft = 9;
        break;

      case ($speed < 103):
        $bft = 10;
        break;

      case ($speed < 118):
        $bft = 11;
        break;

      case ($speed >= 118):
        $bft = 12;
        break;

      default:
        $bft = 100;
        break;
    }
    return $bft;
  }

  /**
   * An array of all supported languages by Wunderground Weather.
   *
   * @return array
   *   An array of languages.
   */
  public function getLanguages() {
    return [
      'AF' => 'Afrikaans',
      'AL' => 'Albanian',
      'AR' => 'Arabic',
      'HY' => 'Armenian',
      'AZ' => 'Azerbaijani',
      'EU' => 'Basque',
      'BY' => 'Belarusian',
      'BU' => 'Bulgarian',
      'LI' => 'British English',
      'MY' => 'Burmese',
      'CA' => 'Catalan',
      'CN' => 'Chinese - Simplified',
      'TW' => 'Chinese - Traditional',
      'CR' => 'Croatian',
      'CZ' => 'Czech',
      'DK' => 'Danish',
      'DV' => 'Dhivehi',
      'NL' => 'Dutch',
      'EN' => 'English',
      'EO' => 'Esperanto',
      'ET' => 'Estonian',
      'FA' => 'Farsi',
      'FI' => 'Finnish',
      'FR' => 'French',
      'FC' => 'French Canadian',
      'GZ' => 'Galician',
      'DL' => 'German',
      'KA' => 'Georgian',
      'GR' => 'Greek',
      'GU' => 'Gujarati',
      'HT' => 'Haitian Creole',
      'IL' => 'Hebrew',
      'HI' => 'Hindi',
      'HU' => 'Hungarian',
      'IS' => 'Icelandic',
      'IO' => 'Ido',
      'ID' => 'Indonesian',
      'IR' => 'Irish Gaelic',
      'IT' => 'Italian',
      'JP' => 'Japanese',
      'JW' => 'Javanese',
      'KM' => 'Khmer',
      'KR' => 'Korean',
      'KU' => 'Kurdish',
      'LA' => 'Latin',
      'LV' => 'Latvian',
      'LT' => 'Lithuanian',
      'ND' => 'Low German',
      'MK' => 'Macedonian',
      'MT' => 'Maltese',
      'GM' => 'Mandinka',
      'MI' => 'Maori',
      'MR' => 'Marathi',
      'MN' => 'Mongolian',
      'NO' => 'Norwegian',
      'OC' => 'Occitan',
      'PS' => 'Pashto',
      'GN' => 'Plautdietsch',
      'PL' => 'Polish',
      'BR' => 'Portuguese',
      'PA' => 'Punjabi',
      'RO' => 'Romanian',
      'RU' => 'Russian',
      'SR' => 'Serbian',
      'SK' => 'Slovak',
      'SL' => 'Slovenian',
      'SP' => 'Spanish',
      'SI' => 'Swahili',
      'SW' => 'Swedish',
      'CH' => 'Swiss',
      'TL' => 'Tagalog',
      'TT' => 'Tatarish',
      'TH' => 'Thai',
      'TR' => 'Turkish',
      'TK' => 'Turkmen',
      'UA' => 'Ukrainian',
      'UZ' => 'Uzbek',
      'VU' => 'Vietnamese',
      'CY' => 'Welsh',
      'SN' => 'Wolof',
      'JI' => 'Yiddish - transliterated',
      'YI' => 'Yiddish - unicode',
    ];
  }

}
