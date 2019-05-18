<?php

namespace Drupal\js;

use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * JsRedirectResponse.
 */
class JsRedirectResponse extends RedirectResponse {

  /**
   * Flag indicating whether redirection should be forced in the browser.
   *
   * @var bool
   */
  protected $force = FALSE;

  /**
   * Sets the whether or not redirection should be forced in the browser.
   *
   * @param bool $force
   *   TRUE or FALSE
   *
   * @return $this
   */
  public function setForce($force = TRUE) {
    $this->force = !!$force;
    return $this;
  }

  /**
   * Indicates whether or not redirection is forced in the browser.
   *
   * @return bool
   *   TRUE or FALSE
   */
  public function isForced() {
    return $this->force;
  }

}
