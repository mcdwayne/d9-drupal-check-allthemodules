<?php

namespace Drupal\vendor_stream_wrapper\PathProcessor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Path Processor for the Vendor Stream Wrapper module.
 */
class VendorStreamWrapperPathProcessor implements InboundPathProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    // Only act on paths that start with /vendor_files/.
    if (strpos($path, '/vendor_files/') === 0) {
      $names = preg_replace('|^\/vendor_files\/|', '', $path);
      $names = str_replace('/', ':', $names);

      return "/vendor_files/$names";
    }

    return $path;
  }

}
