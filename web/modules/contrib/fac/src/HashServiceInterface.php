<?php

namespace Drupal\fac;

/**
 * Interface HashServiceInterface.
 *
 * Provides an interface defining a Fast Autocomplete hash service. The hash
 * service is used to reduce the risk of information leakage by using a hash in
 * the JSON files URL.
 *
 * @package Drupal\fac
 */
interface HashServiceInterface {

  /**
   * Returns a hash.
   *
   * @return string
   *   The requested hash.
   */
  public function getHash();

  /**
   * Validates if the given hash is valid.
   *
   * @param string $hash
   *   The hash to validate.
   *
   * @return bool
   *   TRUE if the hash is valid. FALSE if the hash is invalid.
   */
  public function isValidHash($hash);

  /**
   * Returns the key used for getting a hash.
   *
   * @param bool $renewal
   *   If TRUE, the key is renewed.
   *
   * @return string
   *   The key.
   */
  public function getKey($renewal = FALSE);

}
