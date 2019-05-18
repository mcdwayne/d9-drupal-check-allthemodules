<?php

/**
 * @file
 * Contains \Drupal\apiservices\ApiClientInterface.
 */

namespace Drupal\apiservices;

/**
 * Defines an interface for a client that sends API requests and manages the
 * responses.
 */
interface ApiClientInterface {

  /**
   * Sends an API request.
   *
   * @param \Drupal\apiservices\ApiProviderInterface $provider
   *   The configured API provider.
   * @param array $options
   *   (optional) Additional request options.
   *   - client: An array of settings passed to the underlying HTTP client.
   *
   * @return \Drupal\apiservices\ApiResponseInterface
   *   The API response.
   *
   * @throws \Drupal\apiservices\Exception\EndpointException
   */
  public function request(ApiProviderInterface $provider, array $options = []);

  /**
   * Sends multiple API requests concurrently (if possible).
   *
   * @param \Drupal\apiservices\ApiProviderInterface[] $providers
   *   A list of configured API providers.
   * @param array $options
   *   (optional) Additional request options:
   *   - client: An array of settings passed to the underlying HTTP client.
   *
   * @return array
   *   A list containing both responses and, if one or more requests failed,
   *   exceptions. The keys of the returned array will match the keys of the
   *   API provider list.
   */
  public function requestMultiple(array $providers, array $options = []);

}
