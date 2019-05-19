<?php

namespace Drupal\splashify\PageCache;

use Drupal\Core\PageCache\RequestPolicyInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Disable cache if splash exist.
 */
class SplashifyPath implements RequestPolicyInterface {

  /**
   * {@inheritdoc}
   */
  public function check(Request $request) {
    if (\Drupal::service('splashify.injection')->isSplashExist($request)) {
      \Drupal::service('page_cache_kill_switch')->trigger();
      return static::DENY;
    }

    return static::ALLOW;
  }

}
