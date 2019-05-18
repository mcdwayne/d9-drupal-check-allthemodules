<?php

namespace Drupal\high_contrast;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * This class handles the enabling and
 *
 * @todo This feels somewhat hackish... There is probably a proper core implementation for this.
*/
class HighContrastController extends ControllerBase {

  use HighContrastTrait;

  /**
   * @var string Holds the redirect path for after enabling high contrast.
   */
  var $redirectDestination;

  /**
   * Fetch and store the redirect path.
   */
  function __construct() {
    $this->redirectDestination = \Drupal::request()->query->get('destination');
  }

  /**
   * Enable high contrast.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  function enable() {
    $this->enable_high_contrast();
    return $this->go_back();
  }

  /**
   * Disable high contrast.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  function disable() {
    $this->disable_high_contrast();
    return $this->go_back();
  }

  /**
   * Perform the redirect to the set path.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  function go_back() {
    return new RedirectResponse($this->redirectDestination);
  }

}
