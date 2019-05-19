<?php

namespace Drupal\test_output_viewer\PageCache;

use Drupal\Core\PageCache\RequestPolicyInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * A policy allowing to bypass cache for requests with 'no-cache' parameter.
 *
 * Example: https://example.com/node?no-cache.
 */
class TestOutput implements RequestPolicyInterface {

  /**
   * {@inheritdoc}
   */
  public function check(Request $request) {
    if (preg_match('#/test-output/file/.+\.html$#', $request->getPathInfo())) {
      return static::ALLOW;
    }
  }

}
