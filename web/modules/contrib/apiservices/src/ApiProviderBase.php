<?php

/**
 * @file
 * Contains \Drupal\apiservices\ApiProviderBase.
 */

namespace Drupal\apiservices;

use GuzzleHttp\Psr7\Request;

/**
 * Provides common methods for an API provider.
 */
abstract class ApiProviderBase implements ApiProviderInterface, CacheableApiInterface {

  use CacheableApiTrait;

  /**
   * An endpoint configuration object.
   *
   * @var \Drupal\apiservices\EndpointInterface
   */
  protected $endpoint;

  /**
   * The entity storage object for endpoint configurations.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * Provides a default constructor for use by ApiProviderBase objects.
   *
   * @param string $endpoint_id
   *   The ID of an endpoint configuration object.
   */
  public function __construct($endpoint_id) {
    $this->endpoint = $this->loadEndpoint($endpoint_id);
  }

  /**
   * Gets the storage instance for endpoint configuration entities.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The entity storage object for endpoint configurations.
   */
  protected function entityStorage() {
    if (!$this->entityStorage) {
      $this->entityStorage = \Drupal::entityManager()->getStorage('apiservices_endpoint');
    }
    return $this->entityStorage;
  }

  /**
   * Loads an endpoint configuration object from entity storage.
   *
   * @param string $endpoint_id
   *   The ID of an endpoint configuration object.
   *
   * @return \Drupal\apiservices\EndpointInterface
   *   An endpoint configuration object.
   */
  protected function loadEndpoint($endpoint_id) {
    $endpoint = $this->entityStorage()->load($endpoint_id);
    if (!isset($endpoint)) {
      throw new \UnexpectedValueException(sprintf('The specified endpoint %s does not exist', $endpoint_id));
    }
    return $endpoint;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheId() {
    return implode(':', [$this->cacheContext, $this->getRequestUrl()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getEndpoint() {
    return $this->endpoint;
  }

  /**
   * Creates a request for the current endpoint.
   *
   * Extending classes should override this method to provide support for other
   * request methods (such as POST requests).
   *
   * @return \Psr\Http\Message\RequestInterface
   *   An API request for the HTTP client to send.
   */
  public function getRequest() {
    return new Request('GET', $this->getRequestUrl());
  }

}
