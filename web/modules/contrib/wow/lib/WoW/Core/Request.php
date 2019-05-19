<?php

/**
 * @file
 * Definition of the Request class.
 */

namespace WoW\Core;

/**
 * A request message from a client to a server includes the url of the resource,
 * the parameters to be applied, the locale in use, and the If-Modified-Since
 * header if applicable.
 */
class Request {

  /**
   * The Service.
   *
   * @var ServiceInterface
   */
  protected $service;

  /**
   * Resource URL being linked to. It is the responsibility of the caller to
   * url encode the path: http://$host/api/wow/$path.
   *
   * @var string
   */
  protected $path;

  /**
   * An array of query key/value-pairs (without any URL-encoding) to append to
   * the URL.
   *
   * @var array
   */
  protected $query;

  /**
   * An array containing request headers to send as name/value pairs.
   *
   * @var array
   */
  protected $headers;

  /**
   * Constructs an Request object.
   *
   * @param ServiceInterface $service
   *   A Service object.
   * @param string $path
   *   Resource URL being linked to. It is the responsibility of the caller to
   *   url encode the path: http://$host/api/wow/$path.
   */
  public function __construct(ServiceInterface $service, $path) {
    $this->service = $service;
    $this->path = $path;
    $this->query = array();
    $this->headers = array();
  }

  /**
   * Configures the request with an If-Modified-Since header.
   *
   * @param int $time
   *   A Unix time stamp.
   *
   * @return Request
   *   The Request.
   */
  public function setIfModifiedSince($time) {
    $this->headers['If-Modified-Since'] = gmdate("D, d M Y H:i:s T", $time);
    return $this;
  }

  /**
   * Configures the request with an API compliant locale value.
   *
   * @param string $language
   *   The language used to determine the locale to set.
   *
   * @return Request
   *   The Request.
   */
  public function setLocale($language) {
    // Prepares the query by adding a locale parameter if supported.
    if ($locale = $this->service->getLocale($language)) {
      $this->query['locale'] = $locale;
    }
    return $this;
  }

  /**
   * Adds a non-empty query parameter to the request.
   *
   * @param string $key
   *   The query key.
   * @param string|array $value
   *   The query value.
   *
   * @return Request
   *   The Request.
   */
  public function setQuery($key, $value) {
    if (!empty($value)) {
      $this->query[$key] = is_array($value) ? implode(",", $value) : $value;
    }
    return $this;
  }

  /**
   * Adds a response handler to the request.
   *
   * The handler will then be executed instead of the request object itself.
   *
   * @param string $class
   *   A class string to instantiate.
   *
   * @return HandlerInterface
   *   A handler instance.
   */
  public function onResponse($class = 'WoW\Core\Handler\Handler') {
    return new $class($this->service, $this);
  }

  /**
   * Executes the request.
   *
   * @return Response
   *   The response returned by the service
   */
  public function execute() {
    // The date is used to sign the request, in the following format:
    // Fri, 10 Jun 2011 20:59:24 GMT, but also to time stamp the response.
    $this->headers['Date'] = gmdate("D, d M Y H:i:s T");
    return $this->service->request($this->path, $this->query, $this->headers);
  }

}
