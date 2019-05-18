<?php

namespace Drupal\nasa\Controller;

use Drupal\Core\Url;
// Change following https://www.drupal.org/node/2457593
// See https://www.drupal.org/node/2549395 for deprecate methods information
// use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Html;
// use Html instead SAfeMarkup

/**
 * Controller routines for NASA pages.
 */
class nasaController {

  /**
   * Returns APOD
   * This callback is mapped to the path
   * 'nasa/apod'.
   *
   */
  public function apod() {
    // Default settings.
    $config = \Drupal::config('nasa.settings');
    $nasa_api_key = $config->get('nasa.nasa_api_key');

    // APOD url
    $apod_url = 'https://api.nasa.gov/planetary/apod?hd=True&api_key=' . $nasa_api_key;

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
    $element['#theme'] = 'nasa';

    return $element;
  }
}