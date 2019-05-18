<?php

namespace Drupal\key_auth\PageCache;

use Drupal\key_auth\KeyAuthInterface;
use Drupal\Core\PageCache\RequestPolicyInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Cache policy for pages served from key auth.
 *
 * This policy disallows caching of requests that use key_auth for security
 * reasons. Otherwise responses for authenticated requests can get into the
 * page cache and could be delivered to unprivileged users.
 */
class DisallowKeyAuthRequests implements RequestPolicyInterface {

  /**
   * The key auth service.
   *
   * @var \Drupal\key_auth\KeyAuthInterface
   */
  protected $keyAuth;

  /**
   * Constructs a key authentication page cache policy.
   *
   * @param \Drupal\key_auth\KeyAuthInterface $key_auth
   *   The key auth service..
   */
  public function __construct(KeyAuthInterface $key_auth) {
    $this->keyAuth = $key_auth;
  }

  /**
   * {@inheritdoc}
   */
  public function check(Request $request) {
    if ($this->keyAuth->getKey($request)) {
      return self::DENY;
    }

    return NULL;
  }

}
