<?php

namespace Drupal\sourcepoint\Api;

/**
 * Interface EndpointInterface.
 *
 * @package Drupal\sourcepoint\Api
 */
interface EndpointInterface {

  /**
   * Set the endpoint API key.
   *
   * @param string $api_key
   *   API key.
   *
   * @return EndpointInterface
   *   Endpoint service.
   */
  public function setApiKey($api_key);

  /**
   * Fetches/stores the endpoint script.
   *
   * @return EndpointInterface
   *   Endpoint service.
   */
  public function fetch();

  /**
   * Sets the path where the script will be saved.
   *
   * @return EndpointInterface
   *   Endpoint service.
   */
  public function setPath($path);

  /**
   * Gets the path where the script will be saved.
   *
   * @return string
   *   The local script path.
   */
  public function getPath();

  /**
   * Script to fetch from the API.
   *
   * @return string
   *   Endpoint name.
   */
  public function getName();

  /**
   * Saves the endpoint configuration.
   *
   * @return EndpointInterface
   *   Endpoint service.
   */
  public function saveConfig();

}
