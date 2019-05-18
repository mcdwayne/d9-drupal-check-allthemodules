<?php
namespace Drupal\nasa;

use Drupal\Core\Config\ConfigFactory;

class Apod {

  protected $config_factory;
  protected $nasa_api_key;

  public function __construct(ConfigFactory $config_factory) {
    $this->config_factory = $config_factory;
    $this->nasa_api_key = $this->config_factory->get('nasa.settings')->get('nasa.nasa_api_key');
  }

  public function getApod() {
    // APOD url
    $apod_url = 'https://api.nasa.gov/planetary/apod?hd=True&api_key=' . $this->nasa_api_key;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, $apod_url);
    $result = curl_exec($ch);
    curl_close($ch);

    $decoded = json_decode($result);

    $element['#title'] = 'Astronomic Picture of the Day';
    $element['#image'] = $decoded->url;
    $element['#explanation'] = $decoded->explanation;
    $element['#apod_title'] = $decoded->title;

    return $decoded;
  }
}

