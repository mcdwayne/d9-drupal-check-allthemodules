<?php

namespace Drupal\system_stream_wrapper\StreamWrapper;

use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines a base stream wrapper implementation.
 *
 * ExtensionStreamBase is a read-only Drupal stream wrapper base class for
 * system files located in extensions: modules, themes and installed profile.
 */
abstract class ExtensionStreamBase extends LocalReadOnlyStream {

  // @todo Move this in \Drupal\Core\StreamWrapper\LocalStream in Drupal 9.0.x.
  use StringTranslationTrait;

  /**
   * The current request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * {@inheritdoc}
   */
  public static function getType() {
    return StreamWrapperInterface::LOCAL | StreamWrapperInterface::READ;
  }

  /**
   * Gets the module, theme, or profile name of the current URI.
   *
   * This will return the name of the module, theme or profile e.g.
   * @code SystemStream::getOwnerName('module://foo') @endcode and @code
   * SystemStream::getOwnerName('module://foo/')@endcode will both return @code
   * 'foo'@endcode
   *
   * @return string
   *   The extension name.
   */
  protected function getOwnerName() {
    $uri_parts = explode('://', $this->uri, 2);
    // Remove the trailing filename from the path.
    $length = strpos($uri_parts[1], '/');
    return ($length === FALSE) ? $uri_parts[1] : substr($uri_parts[1], 0, $length);
  }

  /**
   * {@inheritdoc}
   */
  protected function getTarget($uri = NULL) {
    if ($target = strstr(parent::getTarget($uri), '/')) {
      return trim($target, '/');
    }
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getExternalUrl() {
    $dir = $this->getDirectoryPath();
    if (empty($dir)) {
      throw new \InvalidArgumentException("Extension directory for {$this->uri} does not exist.");
    }
    $path = rtrim(base_path() . $dir . '/' . $this->getTarget(), '/');
    return $this->getRequest()->getUriForPath($path);
  }

  /**
   * {@inheritdoc}
   */
  public function dirname($uri = NULL) {
    if (!isset($uri)) {
      $uri = $this->uri;
    }
    else {
      $this->uri = $uri;
    }

    if (isset($uri)) {
      $this->uri = $uri;
    }

    list($scheme) = explode('://', $uri, 2);
    $dirname = dirname($this->getTarget($uri));
    $dirname = $dirname !== '.' ? rtrim("/$dirname", '/') : '';

    return "$scheme://{$this->getOwnerName()}{$dirname}";
  }

  /**
   * Returns the current request object.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   The current request object.
   */
  protected function getRequest() {
    if (!isset($this->request)) {
      $this->request = \Drupal::service('request_stack')->getCurrentRequest();
    }
    return $this->request;
  }

}
