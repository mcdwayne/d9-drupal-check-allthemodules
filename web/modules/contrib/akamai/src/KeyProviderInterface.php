<?php

namespace Drupal\akamai;

/**
 * Interface for Key Providers.
 */
interface KeyProviderInterface {

  /**
   * Confirms if key.repository service exists.
   */
  public function hasKeyRepository();

  /**
   * Retrieves a key from the key module.
   *
   * @param string $key
   *   The id of the key to retrieve.
   *
   * @return string|null
   *   The key value on success, NULL on failure.
   *
   * @throws \Exception
   *   Indicates the key.repository service was not found.
   */
  public function getKey($key);

  /**
   * Retrieves all keys from the key module.
   *
   * @return array
   *   An array of valid keys.
   *
   * @throws \Exception
   *   Indicates the key.repository service was not found.
   */
  public function getKeys();

}
