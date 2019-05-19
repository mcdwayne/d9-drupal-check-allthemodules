<?php

namespace Drupal\pexels;

/**
 * PexelAPI Class.
 */
class PexelsAPI {

  private $token;
  private $numResults;

  /**
   * Construct.
   */
  public function __construct() {
    $config = \Drupal::config('stock_photo_search.settings');

    $this->token = $config->get('pexels_api_key');
    $this->numResults = $config->get('pexels_num_results');
  }

  /**
   * Search for images.
   */
  public function search($query, $page = 1) {
    $url = 'https://api.pexels.com/v1/search?query=' . urlencode($query) .
    '&per_page=' . $this->numResults .
    '&page=' . $page;

    $ch = curl_init($url);

    $options = [
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_HTTPHEADER => ['Accept: application/json'],
      CURLOPT_HTTPHEADER => ['Authorization:' . $this->token],
      CURLOPT_SSL_VERIFYPEER => FALSE,
    ];

    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);

    curl_close($ch);

    if ($response === FALSE) {
      return NULL;
    }
    else {
      $returnArray = [];
      $response = json_decode($response, TRUE);

      foreach ($response as $key => $value) {
        $aux = [];

        if ($key == 'photos') {
          for ($i = 0; $i < count($value); $i++) {
            $aux = [];
            $aux['id'] = $value[$i]['id'];
            $aux['original'] = $value[$i]['src']['original'];
            $aux['small'] = $value[$i]['src']['small'];
            $aux['medium'] = $value[$i]['src']['medium'];
            $aux['large'] = $value[$i]['src']['large'];

            $returnArray[] = $aux;
          }
        }
      }
    }
    return $returnArray;
  }

}
