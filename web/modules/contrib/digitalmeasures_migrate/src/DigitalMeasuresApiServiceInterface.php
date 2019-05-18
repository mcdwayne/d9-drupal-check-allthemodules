<?php

namespace Drupal\digitalmeasures_migrate;

use GuzzleHttp\Exception\GuzzleException;

/**
 * Provides a gateway to the Digital Measures API.
 */
interface DigitalMeasuresApiServiceInterface {

  /**
   * Build the request URL.
   *
   * @param array $options
   *   An array of options. See
   *
   * @return string
   *   A string containing the complete request URL.
   */
  public function getApiURL($options);

  /**
   * @param $options
   *
   * @return mixed
   *
   * @throws GuzzleException
   */
  public function query($options);

  /**
   * Get the basic user profile from the API.
   *
   * @param string $username
   *   The username to query for.
   * @param string $schema_key
   *   The schema key.
   * @param bool $use_test
   *   Optional. TRUE to force using the test API endpoint, FALSE to use prod.
   *
   * @return bool|string
   *   The XML body of the basic profile, FALSE on failure.
   *
   * @throws GuzzleException
   */
  public function getUser($username, $schema_key, $use_test = -1);

  /**
   * Get the extended user profile from the API.
   *
   * @param string $username
   *   The username to query for.
   * @param string $schema_key
   *   The schema key.
   * @param bool $use_test
   *   Optional. TRUE to force using the test API endpoint, FALSE to use prod.
   *
   * @return bool|string
   *   The XML body of the basic profile, FALSE on failure.
   *
   * @throws GuzzleException
   */
  public function getProfile($username, $schema_key, $use_test = -1);
}
