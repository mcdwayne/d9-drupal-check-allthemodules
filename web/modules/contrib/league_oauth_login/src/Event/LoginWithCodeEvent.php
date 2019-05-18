<?php

namespace Drupal\league_oauth_login\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class LoginWithCodeEvent.
 */
class LoginWithCodeEvent extends Event {

  /**
   * Request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * LoginWithCodeEvent constructor.
   */
  public function __construct(Request $request) {
    $this->request = $request;
  }

  /**
   * Get the request.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   The current request.
   */
  public function getRequest() {
    return $this->request;
  }

}
