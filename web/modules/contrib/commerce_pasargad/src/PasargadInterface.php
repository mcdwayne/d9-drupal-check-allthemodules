<?php

namespace Drupal\commerce_pasargad;

/**
 * The Pasargad (an Iranian bank) methods
 */
interface PasargadInterface {

  /**
   * Encrypts $data with the given $private_key.
   *
   * @param array $data
   * @param string $private_key
   *
   * @return mixed
   */
  public static function sign(array $data, $private_key);

  /**
   * Posts data to the bank's gateway.
   *
   * @param string $url
   * @param string $data
   *
   * @return mixed
   */
  public static function post($url, $data);
}