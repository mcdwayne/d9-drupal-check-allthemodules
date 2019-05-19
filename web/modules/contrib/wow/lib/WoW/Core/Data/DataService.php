<?php

/**
 * @file
 * Definition of DataService.
 */

namespace WoW\Core\Data;

use WoW\Core\Request;
use WoW\Core\Response;
use WoW\Core\ServiceInterface;

/**
 * The DataService class is an extension of the Service, adding special handling
 * for data resources, such as cache control by expiration.
 */
class DataService implements ServiceInterface {

  /**
   * The maximum time (in seconds) that retrieved data should be stored in the
   * local cache. Defaults to 30 days.
   *
   * If the server specifies a cache-control directive, this property is ignored.
   *
   * @var int
   */
  const CACHE_LIFETIME = 2592000;

  /**
   * The Service this class decorates.
   *
   * @var ServiceInterface
   */
  protected $service;

  /**
   * The expiration headers.
   *
   * @var array|ExpiresArray
   */
  protected $expires;

  /**
   * Constructs a DataService object.
   *
   * @param ServiceInterface $service
   *   The Service this class decorates.
   * @param array|ExpiresArray $expires
   *   The expires header array keyed by language.
   */
  public function __construct(ServiceInterface $service, $expires) {
    $this->service = $service;
    $this->expires = $expires;
  }

  /**
   * Sets the expires time stamp based on a service's response.
   *
   * @param Response $response
   *   The service response.
   * @param string $entityType
   *   The entity type.
   * @param string $language
   *   (Optional) The language of the resource. Default to English (en).
   */
  public function setExpires(Response $response, $entityType, $language = 'en') {
    $headers = $response->getHeaders();

    if (isset($headers['cache-control'])) {
      // If a cache-control header has been found.
      $cache_control = explode('=', $headers['cache-control']);
      $expires = $response->getDate()->getTimestamp() + $cache_control[1];
    }
    else {
      // If no cache-control header has been found; fill with default values.
      $expires = $response->getDate()->getTimestamp() + self::CACHE_LIFETIME;
    }

    $this->expires[$language][$entityType] = $expires;
  }

  /**
   * (non-PHPdoc)
   * @see \WoW\Core\ServiceInterface::newRequest()
   */
  public function newRequest($path) {
    return new Request($this, drupal_encode_path($path));
  }

  /**
   * (non-PHPdoc)
   * @see \WoW\Core\ServiceInterface::getLocale()
   */
  public function getLocale($language) {
    return $this->service->getLocale($language);
  }

  /**
   * (non-PHPdoc)
   * @see \WoW\Core\ServiceInterface::getLocales()
   */
  public function getLocales() {
    return $this->service->getLocales();
  }

  /**
   * (non-PHPdoc)
   * @see \WoW\Core\ServiceInterface::getRegion()
   */
  public function getRegion() {
    return $this->service->getRegion();
  }

  /**
   * (non-PHPdoc)
   * @see \WoW\Core\ServiceInterface::request()
   */
  public function request($path, array $query = array(), array $headers = array()) {
    return $this->service->request($path, $query, $headers);
  }

}
