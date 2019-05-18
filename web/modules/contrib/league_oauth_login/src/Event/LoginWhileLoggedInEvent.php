<?php

namespace Drupal\league_oauth_login\Event;

use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class..
 */
class LoginWhileLoggedInEvent extends Event {

  /**
   * Request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Redirect URL, if any.
   *
   * @var \Drupal\Core\Url
   */
  protected $redirectUrl;

  /**
   * LoginWithCodeEvent constructor.
   */
  public function __construct(Request $request) {
    $this->request = $request;
  }

  /**
   * Getter.
   *
   * @return \Drupal\Core\Url
   *   URL.
   */
  public function getRedirectUrl() {
    return $this->redirectUrl;
  }

  /**
   * Setter.
   *
   * @param \Drupal\Core\Url $redirectUrl
   *   URL.
   */
  public function setRedirectUrl(Url $redirectUrl) {
    $this->redirectUrl = $redirectUrl;
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
