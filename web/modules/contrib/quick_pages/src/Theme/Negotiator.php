<?php

/**
 * @file
 * Contains \Drupal\quick_pages\Theme\Negotiator.
 */

namespace Drupal\quick_pages\Theme;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;

/**
 * A class which determines the active theme of the page.
 */
class Negotiator implements ThemeNegotiatorInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    // @see: \Drupal\quick_pages\EventSubscriber\RouteSubscriber::alterRoutes()
    if (($route = $route_match->getRouteObject()) && ($theme = $route->getOption('theme'))) {
      return $theme;
    }
    else {
      return NULL;
    }
  }

}
