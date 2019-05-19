<?php

/**
 * @file
 * Definition of \Drupal\smart_login\Theme\SmartLoginNegotiator.
 */

namespace Drupal\smart_login\Theme;

use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Drupal\Core\Routing\RouteMatchInterface;

class SmartLoginNegotiator implements ThemeNegotiatorInterface {
  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $routeMatch) {
    // Use this theme on a certain route.
    $route = $routeMatch->getRouteName();
    return $route == 'smart_login.admin_login' || $route == 'smart_login.admin_password';
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $routeMatch) {
    // Here you return the actual theme name.
    return smart_login_get_admin_theme();
  }
}
