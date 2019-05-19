<?php

namespace Drupal\streamy\StreamWrapper;

use Drupal\Core\Routing\UrlGeneratorTrait;
use League\Flysystem\Util;

trait StreamyURLTrait {

  use UrlGeneratorTrait;

  /**
   * @param $uri
   * @return string
   */
  public function getPrivateExternalURL($uri) {
    $path = str_replace('\\', '/', $this->getPathWithoutScheme($uri));

    $arguments = [
      'scheme'   => $this->getScheme($uri),
      'filepath' => ($path),
    ];

    return $this->url('streamy.serve', $arguments, ['absolute' => TRUE]);
  }

  /**
   * Returns the target file path of a URI.
   *
   * @param string $uri
   *   The URI.
   *
   * @return string
   *   The file path of the URI.
   */
  protected function getPathWithoutScheme($uri) {
    return Util::normalizePath(substr($uri, strpos($uri, '://') + 3));
  }

  /**
   * Returns the scheme from the internal URI.
   *
   * @param string $uri
   *   The URI.
   *
   * @return string
   *   The scheme.
   */
  protected function getScheme($uri) {
    return substr($uri, 0, strpos($uri, '://'));
  }

}
