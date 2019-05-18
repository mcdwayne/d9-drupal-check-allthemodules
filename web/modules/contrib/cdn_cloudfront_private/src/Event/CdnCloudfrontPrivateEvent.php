<?php

namespace Drupal\cdn_cloudfront_private\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class CdnCloudfrontPrivateEvent.
 *
 * An event for determining the cloudfront protection status of a uri.
 */
class CdnCloudfrontPrivateEvent extends Event {

  /**
   * The uri to examine.
   *
   * @var string
   */
  protected $uri;

  /**
   * The original uri, prior to CDN's re-writing.
   *
   * @var string
   */
  protected $originalUri;

  /**
   * Whether to protect and sign the uri.
   *
   * @var bool
   */
  protected $protected = FALSE;

  /**
   * A policy statement to apply to the signature.
   *
   * @var array
   */
  protected $policyStatement = [];

  /**
   * Whether the page should be cacheable after altering the uri.
   *
   * @var bool
   */
  protected $pageCacheable = FALSE;

  /**
   * Method - either url or cookie.
   *
   * @var string
   */
  protected $method = 'url';

  /**
   * Flag for whether the uri needs processing by the Cloudfront client.
   *
   * @var bool
   */
  protected $needsProcessing = TRUE;

  /**
   * Constructor.
   */
  public function __construct($uri, $originalUri) {
    $this->originalUri = $originalUri;
    $this->uri = $uri;
  }

  /**
   * Getter for the signature method.
   *
   * @return string
   *   The signature method.
   */
  public function getMethod() {
    return $this->method;
  }

  /**
   * Method setter.
   *
   * @param string $method
   *   The method, either 'cookie' or 'url'.
   *
   * @throws \InvalidArgumentException
   */
  public function setMethod($method) {
    if (!in_array($method, ['cookie', 'url'])) {
      throw new \InvalidArgumentException('Invalid method.');
    }
    $this->method = $method;
  }

  /**
   * Determine if the URL needs further processing (e.g., cookies set.)
   *
   * @return bool
   *   Boolean indicating need for processing.
   */
  public function needsProcessing() {
    return $this->needsProcessing;
  }

  /**
   * Setter for processing flag.
   *
   * @param bool $needsProcessing
   *   Boolean indicating need for further processing.
   *
   * @throws \InvalidArgumentException
   */
  public function setNeedsProcessing($needsProcessing) {
    if (!is_bool($needsProcessing)) {
      throw new \InvalidArgumentException('Processing value must be a boolean.');
    }
    $this->needsProcessing = $needsProcessing;
  }

  /**
   * Get the page cacheable status.
   *
   * @return bool
   *   Return whether page could be potentially cacheable.
   */
  public function isPageCacheable() {
    return $this->pageCacheable;
  }

  /**
   * Set the page cacheable status.
   *
   * @param bool $pageCacheable
   *   Boolean cacheable flag.
   *
   * @throws \InvalidArgumentException
   */
  public function setPageCacheable($pageCacheable) {
    if (!is_bool($pageCacheable)) {
      throw new \InvalidArgumentException('Cacheable value must be a boolean.');
    }
    $this->pageCacheable = $pageCacheable;
  }

  /**
   * Get the uri to be tested.
   *
   * @return string
   *   The URI.
   */
  public function getUri() {
    return $this->uri;
  }

  /**
   * Setter for the URI.
   *
   * @param string $uri
   *   The URI.
   *
   * @throws \InvalidArgumentException
   */
  public function setUri($uri) {
    if (!is_string($uri)) {
      throw new \InvalidArgumentException('Uri must be a string.');
    }
    $this->uri = $uri;
  }

  /**
   * Gets the original uri.
   *
   * @return string
   *   The original uri.
   */
  public function getOriginalUri() {
    return $this->originalUri;
  }

  /**
   * Return whether the uri is marked as protected.
   *
   * @return bool
   *   Whether the URI is protected.
   */
  public function isProtected() {
    return $this->protected;
  }

  /**
   * Set the protection status.
   *
   * @param bool $protected
   *   Boolean indicating protected status.
   *
   * @throws \InvalidArgumentException
   */
  public function setProtected($protected = TRUE) {
    if (!is_bool($protected)) {
      throw new \InvalidArgumentException('Protected value must be a boolean.');
    }
    $this->protected = $protected;
  }

  /**
   * Get the current policy statement.
   *
   * @return array
   *   The policy statement.
   */
  public function getPolicyStatement() {
    return $this->policyStatement;
  }

  /**
   * Set the policy statement.
   *
   * @param array $statement
   *   The policy statement.
   *
   * @throws \InvalidArgumentException
   */
  public function setPolicyStatement(array $statement) {
    if (!is_array($statement)) {
      throw new \InvalidArgumentException('Policy statement must be an array.');
    }
    $this->policyStatement = $statement;
  }

}
