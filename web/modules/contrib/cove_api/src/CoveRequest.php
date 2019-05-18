<?php

/**
 * @file
 * Contains \Drupal\cove_api\CoveRequest.
 *
 * The Details here are heavily inspired by:
 * https://projects.pbs.org/confluence/display/coveapi/PHP
 */

namespace Drupal\cove_api;


/**
 * Class CoveRequest.
 *
 * @package Drupal\cove_api
 */
class CoveRequest  {

  var $api_id = '';
  var $api_secret = '';

  /**
   * Constructor.
   */
  public function __construct() {
    $config = \Drupal::config('cove_api.covesettings');
    $this->api_id = $config->get('pbs_cove_api_key');
    $this->api_secret = $config->get('pbs_cove_api_secret');
  }

  // Establishes a normalized url:
  //   * Key/Value parameters are sorted
  //   * Values are url encoded and utf-8 encoded
  //
  function normalize_url($url) {
    if ($url == '')
        return '';

    // Break up the url into all the various components
    // we expect this to be a full url
    $parts = parse_url($url);

    // Extract just the query parameters
    $query = $parts['query'];
    if ($query) {
      // break out the parameters from the query, but only as a single
      // array of strings
      $params = explode('&', $query);
      // now we loop through each string and generate a tuple for a multi-array
      $parameters = array();
      foreach ($params as $p) {
        // Split this string into two parts and add to the multi-array
        list($key,$value) = explode('=',$p);
        // do the url encoding while we are looping here
        $parameters[$key] = utf8_encode(urlencode($value));
        //$parameters[$key] = $value;
      }

      // now sort the parameter list
      ksort($parameters);

      // Now combine all the parameters into a single query string
      $newquerystring = http_build_query($parameters);
      $newquerystring = '';
      foreach ($parameters as $key => $value) {
        $newquerystring = $newquerystring.$key.'='.$value.'&';
      }

      $newquerystring = substr($newquerystring,0,strlen($newquerystring)-1);
      // combine everything into the total url
      $parts['query'] = "?".$newquerystring;
    }

    $final_url = $parts['scheme']."://".$parts['host'].$parts['path'].$parts['query'];
    return ($final_url);
  }

  // Using the parameters, generate the hash for the combination
  // of the HTTP verb, the normalized url, the timestamp, nonce, and key
  //
  function calc_signature ($url, $timestamp, $nonce) {
    // Take the url and process it
    $normalized_url = $this->normalize_url($url);

    // Now combine all the required parameters into a single string
    // Note: We are always assuming 'get'
    $string_to_sign = "GET".$normalized_url.$timestamp.$this->api_id.$nonce;

    // And generate the hash using the secret
    $signature = hash_hmac('sha1',$string_to_sign, $this->api_secret);

    return($signature);
  }


  /**
   * Make a request to the PBS COVE API.
   *
   * @param string/array $method
   *   The API method for the request, can be a string or array.
   *   arrays will be exploded to allow for things like
   *   '/programs/408/?args=val'.
   * @param array $args
   *   Associative array of arguments to add to the url. For example:
   *   array('filter_title' => 'nova').
   * @return mixed|string
   *   JSON response from PBS
   */
  function request($method, $args = array()) {

    if (is_array($method)) {
      $method = implode('/', $method);
    }

    $url = 'http://api.pbs.org/cove/v1/' . $method . '/';
    if (!empty($args)) {
      $url .= '?' . http_build_query($args);
    }
    $timestamp = time();
    $nonce = md5(rand());
    $signature = $this->calc_signature($url, $timestamp, $nonce);

    $options = [
      'headers' => [
        'X-PBSAuth-Timestamp' => $timestamp,
        'X-PBSAuth-Consumer-Key' => $this->api_id,
        'X-PBSAuth-Signature' => $signature,
        'X-PBSAuth-Nonce' => $nonce,
      ],
    ];

    try {
      $client = \Drupal::httpClient();
      $request = $client->get($url, $options);
      $response = json_decode($request->getBody($request)->getContents());
    }
    catch (RequestException $e) {
      watchdog_exception('cove_api', $e->getMessage());
    }

    return $response;
  }
}
