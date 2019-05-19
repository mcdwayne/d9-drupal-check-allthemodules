<?php

namespace Drupal\giphy;

/**
 * GiphyAPI Class.
 */
class GiphyAPI {

  private $token;
  private $numResults;
  private $typeSearch;

  /**
   * Construct.
   */
  public function __construct() {
    $config = \Drupal::config('stock_photo_search.settings');

    $this->token = $config->get('giphy_api_key');
    $this->numResults = $config->get('giphy_num_results');
    $this->typeSearch = $config->get('giphy_type_search');
  }

  /**
   * Search for images.
   */
  public function search($query, $page = 1) {
    $url = 'http://api.giphy.com/v1/' . $this->typeSearch . '/search?q=' . urlencode($query) .
    '&limit=' . $this->numResults .
    '&offset=0&api_key=' . $this->token;

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

        if ($key == 'data') {
          for ($i = 0; $i < count($value); $i++) {
            $aux = [];
            $aux['id'] = $value[$i]['id'];
            $aux['original'] = $value[$i]['images']['original']['url'];
            $aux['small'] = $value[$i]['images']['fixed_height_small']['url'];
            $aux['medium'] = $value[$i]['images']['downsized']['url'];

            $returnArray[] = $aux;
          }
        }
      }
    }
    return $returnArray;
  }

}
