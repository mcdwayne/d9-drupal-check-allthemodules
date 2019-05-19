<?php

namespace Drupal\pixabay;

/**
 * PixabayAPI Class.
 */
class PixabayAPI {

  private $token;
  private $numResults;
  private $lang;

  /**
   * Construct.
   */
  public function __construct() {
    $config = \Drupal::config('stock_photo_search.settings');

    $this->token = $config->get('pixabay_api_key');
    $this->numResults = $config->get('pixabay_num_results');
    $this->lang = $config->get('pixabay_lang');
  }

  /**
   * Search for images.
   */
  public function search($query, $page = 1) {
    $url = 'https://pixabay.com/api/?key=' . $this->token .
    '&q=' . urlencode($query);
    '&per_page=' . $this->numResults .
    '&lang=' . $this->lang .
    '&page=' . $page;

    $ch = curl_init($url);

    $options = [
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_HTTPHEADER => ['Accept: application/json'],
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

        if ($key == 'hits') {
          for ($i = 0; $i < count($value); $i++) {
            $aux = [];
            $aux['id'] = $value[$i]['id'];
            $aux['original'] = $value[$i]['imageURL'];
            $aux['small'] = $value[$i]['previewURL'];
            $aux['medium'] = $value[$i]['webformatURL'];
            $aux['large'] = $value[$i]['largeImageURL'];

            $returnArray[] = $aux;
          }
        }
      }
    }
    return $returnArray;
  }

}
