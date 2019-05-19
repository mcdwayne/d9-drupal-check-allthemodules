<?php

namespace Drupal\oauth\PageCache;

use Drupal\Core\PageCache\RequestPolicyInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Cache policy for pages served from OAuth.
 *
 * This policy disallows caching of requests that use OAuth for security
 * reasons. Otherwise responses for authenticated requests can get into the
 * page cache and could be delivered to unprivileged users.
 */
class DisallowOauthRequests implements RequestPolicyInterface {

  /**
   * {@inheritdoc}
   */
  public function check(Request $request) {
    $authorization = $request->headers->get('authorization');
    if (strpos($authorization, 'OAuth') === 0) {
      return self::DENY;
    }
  }

}

