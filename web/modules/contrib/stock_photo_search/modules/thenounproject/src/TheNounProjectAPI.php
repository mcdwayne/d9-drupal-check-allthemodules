<?php

namespace Drupal\thenounproject;

/**
 * TheNounProjectAPI Class.
 */
class TheNounProjectAPI {

  private $token;
  private $secret;
  private $numResults;

  /**
   * Construct.
   */
  public function __construct() {
    $config = \Drupal::config('stock_photo_search.settings');

    $this->token = $config->get('thenounproject_api_key');
    $this->secret = $config->get('thenounproject_secret_key');
    $this->numResults = $config->get('thenounproject_num_results');
  }

  /**
   * Search for images.
   */
  public function search($query, $page = 1) {

    $mt = microtime();
    $rand = mt_rand();
    $nonce = md5($mt . $rand);
    $timestamp = time();

    $url = 'http://api.thenounproject.com/icons/' . urlencode($query);

    $param_string = 'limit=' . $this->numResults .
        '&oauth_consumer_key=' . $this->token .
        '&oauth_nonce=' . $nonce .
        '&oauth_signature_method=HMAC-SHA1&oauth_timestamp=' . $timestamp .
        '&oauth_version=1.0';

    $base_string = 'GET&' . rawurlencode($url) . '&' . rawurlencode($param_string);
    $sign_key = rawurlencode($this->secret) . '&';

    $signature = base64_encode(hash_hmac('sha1', $base_string, $sign_key, TRUE));

    $curl_header = [
      'Authorization: OAuth oauth_consumer_key="' . rawurlencode($this->token) .
      '",oauth_nonce="' . rawurlencode($nonce) .
      '",oauth_signature="' . rawurlencode($signature) .
      '",oauth_signature_method="HMAC-SHA1",oauth_timestamp="' . rawurlencode($timestamp) .
      '",oauth_version="1.0"',
    ];

    $ch = curl_init();
    $url .= '?limit=' . $this->numResults;

    curl_setopt($ch, CURLOPT_HTTPHEADER, $curl_header);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
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

        if ($key == 'icons') {
          for ($i = 0; $i < count($value); $i++) {
            $aux = [];
            $aux['id'] = $value[$i]['id'];
            $aux['original'] = $value[$i]['preview_url'];
            $aux['small'] = $value[$i]['preview_url_42'];
            $aux['medium'] = $value[$i]['preview_url_84'];

            $returnArray[] = $aux;
          }
        }
      }
    }
    return $returnArray;
  }

}
