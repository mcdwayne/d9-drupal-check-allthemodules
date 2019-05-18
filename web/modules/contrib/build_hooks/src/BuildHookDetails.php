<?php

namespace Drupal\build_hooks;

/**
 * Class BuildHookDetails.
 *
 * Holds information to make the call to an external service for a build hook.
 */
class BuildHookDetails {

  /**
   * The url to call.
   *
   * @var string
   */
  protected $url;

  /**
   * The method to use (POST,GET,...)
   *
   * @var string
   */
  protected $method;

  /**
   * The body of the request.
   *
   * @var array
   */
  protected $body;

  /**
   * BuildHookDetails constructor.
   */
  public function __construct() {
    $this->url = '';
    $this->body = [];
    $this->method = '';
  }

  /**
   * Get the url.
   *
   * @return string
   *   The url.
   */
  public function getUrl(): string {
    return $this->url;
  }

  /**
   * Set the url.
   *
   * @param string $url
   *   The url.
   */
  public function setUrl(string $url) {
    $this->url = $url;
  }

  /**
   * Get the method.
   *
   * @return string
   *   The method.
   */
  public function getMethod(): string {
    return $this->method;
  }

  /**
   * Set the method.
   *
   * @param string $method
   *   The method.
   */
  public function setMethod(string $method) {
    $this->method = $method;
  }

  /**
   * Get the body.
   *
   * @return array
   *   The body.
   */
  public function getBody(): array {
    return $this->body;
  }

  /**
   * Set the body.
   *
   * @param array $body
   *   The array.
   */
  public function setBody(array $body) {
    $this->body = $body;
  }

}
