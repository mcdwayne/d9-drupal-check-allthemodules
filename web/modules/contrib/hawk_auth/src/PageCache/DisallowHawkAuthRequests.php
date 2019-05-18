<?php

/**
 * @file
 * Contains Drupal\hawk_auth\PageCache\DisallowHawkRequests.
 */

namespace Drupal\hawk_auth\PageCache;

use Dragooon\Hawk\Server\ServerInterface;
use Drupal\Core\PageCache\RequestPolicyInterface;
use Symfony\Component\HttpFoundation\Request;


/**
 * Cache policy for requests served through Hawk authentication.
 *
 * Disable any page caching for requests containing Hawk authorization header to
 * avoid leaking of cached pages to anonymous users or other users who may not
 * have the required permissions. Otherwise it would cache the URL and serve
 * the same page back to other users.
 */
class DisallowHawkAuthRequests implements RequestPolicyInterface {

  /**
   * Hawk server library.
   *
   * @var \Dragooon\Hawk\Server\ServerInterface
   */
  protected $server;

  /**
   * Constructs a DisallowHawkAuthRequests object.
   *
   * @param \Dragooon\Hawk\Server\ServerInterface $server
   *   Library for hawk's server functions.
   */
  public function __construct(ServerInterface $server) {
    $this->server = $server;
  }

  /**
   * {@inheritdoc}
   */
  public function check(Request $request) {
    if ($this->server->checkRequestForHawk($request->headers->get('authorization'))) {
      return self::DENY;
    }
  }

}
