<?php

namespace Drupal\tag1quo\Adapter\Http;

use Drupal\tag1quo\Adapter\Core\Core;

/**
 * Class Request.
 *
 * @internal This class is subject to change.
 */
class Request {

  const METHOD_HEAD = 'HEAD';
  const METHOD_GET = 'GET';
  const METHOD_POST = 'POST';
  const METHOD_PUT = 'PUT';
  const METHOD_PATCH = 'PATCH';
  const METHOD_DELETE = 'DELETE';
  const METHOD_PURGE = 'PURGE';
  const METHOD_OPTIONS = 'OPTIONS';
  const METHOD_TRACE = 'TRACE';
  const METHOD_CONNECT = 'CONNECT';

  /**
   * @var \Drupal\tag1quo\Adapter\Http\ParameterBag
   */
  public $cookies;

  /**
   * The uppercase request method.
   *
   * @var string
   */
  protected $method;

  /**
   * The headers.
   *
   * @var \Drupal\tag1quo\Adapter\Http\ParameterBag
   */
  public $headers;

  /**
   * The request options.
   *
   * @var \Drupal\tag1quo\Adapter\Http\ParameterBag
   */
  public $options;

  /**
   * @var \Drupal\tag1quo\Adapter\Http\ParameterBag
   */
  public $query;

  /**
   * The base request URI.
   *
   * @var string
   */
  protected $uri;

  /**
   * Request constructor.
   *
   * @param string $method
   *   The HTTP method.
   * @param string $uri
   *   The request URI.
   * @param array $options
   *   The request options.
   */
  public function __construct($method, $uri, array $options = array()) {
    $this->setMethod($method);
    $this->setUri($uri);
    $this->setOptions($options);
  }

  /**
   * Retrieves the request method.
   *
   * @return string
   */
  public function getMethod() {
    return $this->method;
  }

  /**
   * Retrieves set options as an array.
   *
   * @return array
   */
  public function getOptions() {
    return $this->options->all();
  }

  /**
   * Retrieves the request URI.
   *
   * @return string
   */
  public function getUri() {
    $query = http_build_query($this->query->all());
    return $this->uri . ($query ? "?$query" : '');
  }

  /**
   * Sets the method.
   *
   * @param string $method
   *   The method to set.
   */
  public function setMethod($method) {
    if ($constant = @constant('\Drupal\tag1quo\Adapter\Http\Request::METHOD_' . strtoupper($method))) {
      $this->method = $constant;
    }
    else {
      throw new \InvalidArgumentException(sprintf('Invalid method: %s', $method));
    }
  }

  /**
   * Sets the options.
   *
   * @param array $options
   *   The options to set.
   */
  public function setOptions(array $options) {
    $this->options = new ParameterBag($options);
    $this->headers = new ParameterBag($this->options->get('headers', array()));
    $this->cookies = new ParameterBag($this->headers->get('Cookie', array()));
  }

  /**
   * Sets the URI.
   *
   * @param string $uri
   *   The URI to set.
   */
  public function setUri($uri) {
    $parts = Core::create()->parseUrl((string) $uri);
    $this->uri = $parts['path'];
    $this->query = new ParameterBag($parts['query']);
  }

}
