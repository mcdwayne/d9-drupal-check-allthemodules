<?php

namespace Drupal\az_blob_fs\PathProcessor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Symfony\Component\HttpFoundation\Request;

class PathProcessorAzBlob implements InboundPathProcessorInterface {

  /**
   * Processes the inbound path.
   *
   * @param string $path
   *   The path to process, with a leading slash.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HttpRequest object representing the current request.
   *
   * @return string
   *   The processed path.
   */
  public function processInbound($path, Request $request) {
    if (strpos($path, '/azblob/files/') === 0 && !$request->query->has('file')) {
      $file_path = preg_replace('|^\/azblob\/files\/|', '', $path);
      $request->query->set('file', $file_path);
      // We return the route we want to match.
      return '/azblob/files';
    }
    return $path;
  }
}