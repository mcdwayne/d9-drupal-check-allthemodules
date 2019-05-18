<?php

namespace Drupal\system_stream_wrapper\StreamWrapper;

use Drupal\Core\StreamWrapper\StreamWrapperInterface;

/**
 * Provides a base class for all stream wrappers.
 */
abstract class StreamWrapperBase implements StreamWrapperInterface {

  /**
   * Stream context resource.
   *
   * @var resource
   */
  public $context;

  /**
   * A generic resource handle.
   *
   * @var resource
   */
  public $handle = NULL;

  /**
   * Instance URI (stream).
   *
   * A stream is referenced as "scheme://target".
   *
   * @var string
   */
  protected $uri;

  /**
   * {@inheritdoc}
   */
  function setUri($uri) {
    if (strpos($uri, '://') === FALSE) {
      // The delimiter ('://') was not found in $uri, malformed $uri passed.
      throw new \InvalidArgumentException("Malformed uri parameter passed: {$this->uri}");
    }
    $this->uri = $uri;
  }

  /**
   * {@inheritdoc}
   */
  function getUri() {
    return $this->uri;
  }

}
