<?php

/**
 * @file
 * Contains \Drupal\prefetch_cache\RequestPolicy\GetRequest.
 */

namespace Drupal\prefetch_cache\RequestPolicy;

use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\PageCache\RequestPolicyInterface;

/**
 * A policy denying delivery of cached pages for request methods except GET.
 *
 */
class GetRequest implements RequestPolicyInterface {

  /**
   * {@inheritdoc}
   */
  public function check(Request $request) {
    if ($request->getMethod() != Request::METHOD_GET) {
      return static::DENY;
    }
    return static::ALLOW;
  }
}
