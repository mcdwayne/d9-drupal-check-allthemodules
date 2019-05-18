<?php

namespace Drupal\ckan_connect\Client;

/**
 * Provides an interface defining a CKAN client.
 */
interface CkanClientInterface {

  /**
   * Gets the API URL.
   *
   * @return string
   *   The API URL.
   */
  public function getApiUrl();

  /**
   * Sets the API URL.
   *
   * @param string $api_url
   *   The API URL.
   *
   * @return \Drupal\ckan_connect\Client\CkanClientInterface
   *   This CkanClientInterface instance.
   */
  public function setApiUrl($api_url);

  /**
   * Gets the API key.
   *
   * @return string
   *   The API key.
   */
  public function getApiKey();

  /**
   * Sets the API key.
   *
   * @param string $api_key
   *   The API key.
   *
   * @return \Drupal\ckan_connect\Client\CkanClientInterface
   *   This CkanClientInterface instance.
   */
  public function setApiKey($api_key);

  /**
   * Get data from the CKAN endpoint.
   *
   * @param string $path
   *   The path of the action.
   * @param array $query
   *   The key pair parameters.
   *
   * @return \stdClass
   *   A response object.
   */
  public function get($path, array $query = []);

}
