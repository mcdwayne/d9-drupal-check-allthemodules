<?php

/**
 * @file
 * Contains \Drupal\freegeoip_views\FreegeoipGetService.
 */

namespace Drupal\freegeoip_views;


/**
 * Class FreegeoipGetService.
 *
 * @package Drupal\freegeoip_views
 */
class FreegeoipGetService {
  /**
   * Constructor.
   */
  public function __construct() {

  }

  public function getFreegeoipDetails() {
      $curl = curl_init();
      $url_opt = 'http://freegeoip.net/json/'. $_SERVER['REMOTE_ADDR'];
      curl_setopt_array($curl, array(
        CURLOPT_URL => $url_opt,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
          "cache-control: no-cache",
        ),
      ));
      $freegeoip = curl_exec($curl);
      $error = curl_error($curl);
      curl_close($curl);
      if ($error) {
        // Handle error;
      }
      return $freegeoip;
  }
}
