<?php

/**
 * @file
 * Contains \Drupal\apiservices\ApiProviderInterface.
 */

namespace Drupal\apiservices;

/**
 * Defines an interface for an API provider that can build a request using an
 * endpoint configuration entity.
 */
interface ApiProviderInterface {

  /**
   * Gets the current endpoint being used by the provider.
   *
   * @return \Drupal\apiservices\EndpointInterface
   *   An endpoint configuration entity.
   */
  public function getEndpoint();

  /**
   * Creates a request for the current endpoint.
   *
   * @return \Psr\Http\Message\RequestInterface
   *   An API request for the HTTP client to send.
   */
  public function getRequest();

  /**
   * Gets a URL that can be used to make an API request.
   *
   * @return string
   *   The URL for this endpoint request.
   */
  public function getRequestUrl();

  /**
   * Sets the replacement value for a placeholder in the URL.
   *
   * @param string $placeholder
   *   The placeholder value in the URL.
   * @param string $replacement
   *   The replacement value for the placeholder.
   *
   * @return $this
   */
  public function setPlaceholder($placeholder, $replacement);

  /**
   * Sets a URL query parameter.
   *
   * @param string $parameter
   *   The query parameter.
   * @param string|array $value
   *   A string, or list of strings, containing parameter values.
   *
   * @return $this
   */
  public function setQueryParameter($parameter, $value);

}
