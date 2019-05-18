<?php
/**
 * @file
 * Containers \Drupal\block_render\Theme\RenderNegotiator.
 */

namespace Drupal\block_render\Theme;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;

/**
 * Theme Negotiation for Block Rendering.
 */
class RenderNegotiator implements ThemeNegotiatorInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    // Use this theme on a certain route.
    return $route_match->getRouteName() == 'block_render.block';
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    return $route_match->getParameter('block')->getTheme();
  }

}
