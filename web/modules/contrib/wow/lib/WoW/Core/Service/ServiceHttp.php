<?php

/**
 * @file
 * Definition of ServiceHttp.
 */

namespace WoW\Core\Service;

use WoW\Core\Request;
use WoW\Core\Response;
use WoW\Core\ServiceInterface;

/**
 * Service performs GET operations against battle.net API.
 *
 * This service is meant to be used for sending anonymous requests.
 */
class ServiceHttp implements ServiceInterface {

 /**
  * The region for this service.
  *
  * @var string
  */
  protected $region;

  /**
   * The list of locales supported by this service.
   *
   * @var array
   */
  protected $locales;

  /**
   * @param string $region
   *   The service region.
   * @param array $locales
   *   The service locales.
   */
  public function __construct($region, array $locales) {
    $this->region = $region;
    $this->locales = $locales;
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
    return isset($this->locales[$language]) ? $this->locales[$language] : NULL;
  }

  /**
   * (non-PHPdoc)
   * @see \WoW\Core\ServiceInterface::getLocales()
   */
  public function getLocales() {
    return $this->locales;
  }

  /**
   * (non-PHPdoc)
   * @see \WoW\Core\ServiceInterface::getRegion()
   */
  public function getRegion() {
    return $this->region;
  }

  /**
   * (non-PHPdoc)
   * @see \WoW\Core\ServiceInterface::request()
   */
  public function request($path, array $query = array(), array $headers = array()) {
    // Prepares the URL by adding an HTTP scheme for non-authenticated request.
    $options = array('absolute' => TRUE, 'external' => TRUE, 'query' => $query);
    $url = url("http://{$this->getHost()}/api/wow/$path", $options);

    return $this->__request($url, array('headers' => $headers));
  }

  /**
   * @return string
   *   Service's host.
   *
   * @see wow_service_info()
   */
  protected function getHost() {
    return wow_service_info($this->region)->host;
  }

  /**
   * Performs an HTTP request.
   *
   * @see drupal_http_request()
   */
  protected function __request($url, array $options) {
    return new Response(drupal_http_request($url, $options));
  }

}
