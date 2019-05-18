<?php

/**
 * @file
 * Contains \Drupal\dcat_landingpage\Theme\LandingpageThemeNegotiator.
 */

namespace Drupal\dcat_landingpage\Theme;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;

/**
 * Class LandingpageThemeNegotiator.
 */
class LandingpageThemeNegotiator implements ThemeNegotiatorInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    return $route_match->getRouteName() == 'view.dataset_landingpage.page';
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    return 'stark';
  }

}
