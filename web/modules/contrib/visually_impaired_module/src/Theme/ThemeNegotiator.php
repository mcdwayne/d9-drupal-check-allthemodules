<?php

namespace Drupal\visually_impaired_module\Theme;

use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Defines the theme negotiator.
 */
class ThemeNegotiator implements ThemeNegotiatorInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $applies = TRUE;
    return $applies;
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    $config = \Drupal::config('visually_impaired_module.visually_impaired_module.settings');

    $is_admin = \Drupal::service('router.admin_context')->isAdminRoute($route_match->getRouteObject());

    if ((isset($_COOKIE['visually_impaired'])) && ($_COOKIE['visually_impaired'] == 'on') && ($is_admin == FALSE)) {
      return $config->get('visually_impaired_theme');
    }
  }

}
