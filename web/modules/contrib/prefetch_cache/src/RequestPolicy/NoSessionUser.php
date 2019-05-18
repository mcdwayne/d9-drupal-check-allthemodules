<?php

/**
 * @file
 * Contains \Drupal\prefetch_cache\RequestPolicy\NoSessionUser.
 */

namespace Drupal\prefetch_cache\RequestPolicy;

use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\PageCache\RequestPolicyInterface;

/**
 * A policy denying delivery of cached pages if no session user is set.
 *
 */
class NoSessionUser implements RequestPolicyInterface {

  /**
   * {@inheritdoc}
   */
  public function check(Request $request) {
    if (!$request->getSession()->get('uid')) {
      return static::DENY;
    }
    return static::ALLOW;
  }
}
