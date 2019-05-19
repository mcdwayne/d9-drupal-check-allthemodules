<?php

namespace Drupal\sourcepoint\Api;

/**
 * Class EndpointManager.
 *
 * @package Drupal\sourcepoint\Api
 */
class EndpointManager implements EndpointManagerInterface {

  /**
   * List of collected endpoints.
   *
   * @var \Drupal\sourcepoint\Api\EndpointInterface[]
   */
  protected $endpoints = [];

  /**
   * {@inheritdoc}
   */
  public function addEndpoint(EndpointInterface $endpoint) {
    // Register endpoint.
    $this->endpoints[$endpoint->getName()] = $endpoint;
  }

  /**
   * {@inheritdoc}
   */
  public function getEndpoint($endpoint_name) {
    if (isset($this->endpoints[$endpoint_name])) {
      return $this->endpoints[$endpoint_name];
    }
    throw new \Exception('Unknown endpoint ' . $endpoint_name . '.
      Available endpoints: ' . implode(', ', array_keys($this->endpoints)));
  }

  /**
   * {@inheritdoc}
   */
  public function getEndpoints() {
    return $this->endpoints;
  }

}
