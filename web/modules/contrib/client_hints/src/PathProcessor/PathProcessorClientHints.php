<?php

namespace Drupal\client_hints\PathProcessor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a path processor to rewrite image styles URLs.
 *
 * As the route system does not allow arbitrary amount of parameters convert
 * the file path to a query parameter on the request.
 *
 * This processor handles two different cases:
 * - public image styles: In order to allow the webserver to serve these files
 *   directly, the route is registered under the same path as the image style so
 *   it took over the first generation. Therefore the path processor converts
 *   the file path to a query parameter.
 * - private image styles: In contrast to public image styles, private
 *   derivatives are already using system/files/styles. Similar to public image
 *   styles, it also converts the file path to a query parameter.
 */
class PathProcessorClientHints implements InboundPathProcessorInterface {

  /**
   * The stream wrapper manager service.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * Constructs a new PathProcessorImageStyles object.
   *
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager service.
   */
  public function __construct(StreamWrapperManagerInterface $stream_wrapper_manager) {
    $this->streamWrapperManager = $stream_wrapper_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {

    if ($file = $request->query->get('file')) {

      // Make sure this is using the public file system.
      $file_public_path = \Drupal::service('settings')->get('file_public_path');
      if (strpos($file, '/' . $file_public_path . '/') === 0) {
        $request->query->set('file', substr($file, strlen($file_public_path) + 2));
      }
    }

    return $path;

  }

}
