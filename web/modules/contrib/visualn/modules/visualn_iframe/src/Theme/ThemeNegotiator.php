<?php

namespace Drupal\visualn_iframe\Theme;
 
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Symfony\Component\Routing\Route;

/**
 * Class ThemeNegotiator.
 */
class ThemeNegotiator implements ThemeNegotiatorInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $route = $route_match->getRouteObject();
    if (!$route instanceof Route) {
      return FALSE;
    }
    $option = $route->getOption('_custom_theme');
    if (!$option) {
      return FALSE;
    }
 
    return $option == 'stable';
  }
 
  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    return 'stable';
  }
}
