<?php

namespace Drupal\webserver_auth\PageCache\RequestPolicy;

use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\PageCache\RequestPolicyInterface;
use Drupal\webserver_auth\WebserverAuthHelper;

/**
 * A policy declining delivery of cached pages when server remote user variable is set.
 */
class RemoteUserIsSet implements RequestPolicyInterface {

  /**
   * Helper class that brings some helper functionality related to webserver authentication.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $helper;

  /**
   * Constructs a new page cache session policy.
   *
   * @param \Drupal\webserver_auth\WebserverAuthHelper $helper
   *   Helper class that brings some functionality related to webserver authentication.
   */
  public function __construct(WebserverAuthHelper $helper) {
    $this->helper = $helper;
  }

  /**
   * {@inheritdoc}
   */
  public function check(Request $request) {
    // Preventing page from being took from cache if remote_user is set.
    if ($this->helper->getRemoteUser($request)) {
      return static::DENY;
    }
  }
}
