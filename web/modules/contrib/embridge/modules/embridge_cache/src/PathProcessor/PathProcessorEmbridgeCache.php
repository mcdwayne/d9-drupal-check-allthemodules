<?php

namespace Drupal\embridge_cache\PathProcessor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a path processor to rewrite embridge cache URLs.
 *
 * As the route system does not allow arbitrary amount of parameters convert
 * the file path to a query parameter on the request.
 *
 * @see \Drupal\image\PathProcessor\PathProcessorImageStyles.
 */
class PathProcessorEmbridgeCache implements InboundPathProcessorInterface {

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
    $directory_path = $this->streamWrapperManager->getViaScheme('public')->getDirectoryPath();
    if (strpos($path, '/' . $directory_path . '/embridge_cache/') === 0) {
      $path_prefix = '/' . $directory_path . '/embridge_cache/';
    }
    else {
      return $path;
    }

    // Strip out path prefix.
    $rest = preg_replace('|^' . preg_quote($path_prefix, '|') . '|', '', $path);

    // Get the image style, scheme and path.
    if (substr_count($rest, '/') >= 3) {
      list($catalog_id, $conversion, $scheme, $asset) = explode('/', $rest, 4);

      // Set the file as query parameter.
      $request->query->set('file', $asset);

      return $path_prefix . $catalog_id . '/' . $conversion . '/' . $scheme;
    }
    else {
      return $path;
    }
  }

}
