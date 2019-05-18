<?php
/**
 * @file
 * Contains \Drupal\live_weather\LiveWeather.
 */

namespace Drupal\live_weather;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Serialization\Json;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use Drupal\Component\Utility\Unicode;

/**
 * Live weather.
 */
class LiveWeather implements LiveWeatherInterface {

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a location form object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The Guzzle HTTP client.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(ClientInterface $http_client, LoggerInterface $logger) {
    $this->httpClient = $http_client;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client'),
      $container->get('logger.factory')
    );
  }

  /**
   * Get location data.
   */
  public function locationCheck($woeid = NULL, $filter = '', $unit = 'f') {
    $settings = \Drupal::config('live_weather.settings')->get('settings');
    $data = '';
    $url = 'https://weather-ydn-yql.media.yahoo.com/forecastrss';
    $app_id = isset($settings['app_id'])?$settings['app_id']:'';
    $consumer_key = isset($settings['consumer_key'])?$settings['consumer_key']:'';
    $consumer_secret = isset($settings['consumer_secret'])?$settings['consumer_secret']:'';
    if(!empty($app_id) && !empty($consumer_key) && !empty($consumer_secret)) {
      $query = array('woeid' => $woeid,'format' => 'json',);
      $oauth = array(
      'oauth_consumer_key' => $consumer_key,
      'oauth_nonce' => uniqid(mt_rand(1, 1000)),
      'oauth_signature_method' => 'HMAC-SHA1',
      'oauth_timestamp' => time(),
      'oauth_version' => '1.0');
      $base_info = $this->buildBaseString($url, 'GET', array_merge($query, $oauth));
      $composite_key = rawurlencode($consumer_secret) . '&';
      $oauth_signature = base64_encode(hash_hmac('sha1', $base_info, $composite_key, true));
      $oauth['oauth_signature'] = $oauth_signature;

      $header = array(
        $this->buildAuthorizationHeader($oauth),
        'Yahoo-App-Id: ' . $app_id
      );
      $options = array(
        CURLOPT_HTTPHEADER => $header,
        CURLOPT_HEADER => false,
        CURLOPT_URL => $url . '?' . http_build_query($query),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false
      );
      $ch = curl_init();
      curl_setopt_array($ch, $options);
      $response = curl_exec($ch);
      curl_close($ch);    
      if (!empty($response)) {
        $data = Json::decode($response);
        if (empty($data) || !is_array($data)) {
          $this->logger->warning('Failed to get data. Please check your API details.');
        }
      }
    }
    return $data;
  }

  /**
   * Check Day or Night.
   */
  public static function checkDayNight($date, $sunrise, $sunset) {
    $position = Unicode::strpos($date, ":");
    $tpb = Unicode::substr($date, $position - 2, 8);
    $actual_time = strtotime($tpb);
    $sunrise_time = strtotime($sunrise);
    $sunset_time = strtotime($sunset);
    if ($actual_time > $sunrise_time && $actual_time < $sunset_time) {
      return 'd';
    }
    else {
      return 'n';
    }
    return 'd';
  }

  /**
   * Get Wind Direction.
   */
  public static function windDirection($direction) {
    if ($direction >= 348.75 && $direction <= 360) {
      $direction = "N";
    }
    elseif ($direction >= 0 && $direction < 11.25) {
      $direction = "N";
    }
    elseif ($direction >= 11.25 && $direction < 33.75) {
      $direction = "NNE";
    }
    elseif ($direction >= 33.75 && $direction < 56.25) {
      $direction = "NE";
    }
    elseif ($direction >= 56.25 && $direction < 78.75) {
      $direction = "ENE";
    }
    elseif ($direction >= 78.75 && $direction < 101.25) {
      $direction = "E";
    }
    elseif ($direction >= 101.25 && $direction < 123.75) {
      $direction = "ESE";
    }
    elseif ($direction >= 123.75 && $direction < 146.25) {
      $direction = "SE";
    }
    elseif ($direction >= 146.25 && $direction < 168.75) {
      $direction = "SSE";
    }
    elseif ($direction >= 168.75 && $direction < 191.25) {
      $direction = "S";
    }
    elseif ($direction >= 191.25 && $direction < 213.75) {
      $direction = "SSW";
    }
    elseif ($direction >= 213.75 && $direction < 236.25) {
      $direction = "SW";
    }
    elseif ($direction >= 236.25 && $direction < 258.75) {
      $direction = "WSW";
    }
    elseif ($direction >= 258.75 && $direction < 281.25) {
      $direction = "W";
    }
    elseif ($direction >= 281.25 && $direction < 303.75) {
      $direction = "WNW";
    }
    elseif ($direction >= 303.75 && $direction < 326.25) {
      $direction = "NW";
    }
    elseif ($direction >= 326.25 && $direction < 348.75) {
      $direction = "NNW";
    }
    return $direction;
  }

  /**
   * buildBaseString.
   */
  public static function buildBaseString($baseURI, $method, $params) {
    $r = array();
    ksort($params);
    foreach($params as $key => $value) {
        $r[] = "$key=" . rawurlencode($value);
    }
    return $method . "&" . rawurlencode($baseURI) . '&' . rawurlencode(implode('&', $r));
  }

  /**
   * buildAuthorizationHeader.
   */
  public static function buildAuthorizationHeader($oauth) {
    $r = 'Authorization: OAuth ';
    $values = array();
    foreach($oauth as $key=>$value) {
        $values[] = "$key=\"" . rawurlencode($value) . "\"";
    }
    $r .= implode(', ', $values);
    return $r;
  }
}
