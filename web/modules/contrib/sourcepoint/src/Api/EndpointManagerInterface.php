<?php

namespace Drupal\sourcepoint\Api;

/**
 * Interface EndpointManagerInterface.
 *
 * @package Drupal\sourcepoint\Api
 */
interface EndpointManagerInterface {

  /**
   * Collect tagged endpoint service.
   */
  public function addEndpoint(EndpointInterface $endpoint);

  /**
   * Get endpoint by name.
   *
   * @param string $endpoint_name
   *   Endpoint name.
   *
   * @return \Drupal\sourcepoint\Api\EndpointInterface
   *   Endpoint service.
   */
  public function getEndpoint($endpoint_name);

  /**
   * Get list of endpoints.
   *
   * @return \Drupal\sourcepoint\Api\EndpointInterface[]
   *   List of endpoint services.
   */
  public function getEndpoints();

}
