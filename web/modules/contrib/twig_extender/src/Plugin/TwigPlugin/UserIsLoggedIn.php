<?php

namespace Drupal\twig_extender\Plugin\TwigPlugin;

use Drupal\twig_extender\Plugin\Twig\TwigPluginBase;

/**
 * The plugin for check authenticated user.
 *
 * @TwigPlugin(
 *   id = "twig_extender_user_is_logged_in",
 *   label = @Translation("Check if user is logged in"),
 *   type = "function",
 *   name = "user_is_logged_in",
 *   function = "isLoggedIn"
 * )
 */
class UserIsLoggedIn extends TwigPluginBase {

  /**
   * Implement user is logged in function.
   */
  public function isLoggedIn() {
    return \Drupal::currentUser()->isAuthenticated();
  }

}
