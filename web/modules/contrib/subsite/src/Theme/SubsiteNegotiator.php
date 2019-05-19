<?php

/**
 * @file
 * Contains \Drupal\subsite\Theme\SubsiteNegotiator.
 */

namespace Drupal\subsite\Theme;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\DefaultNegotiator;
use Drupal\node\Entity\Node;
use Drupal\subsite\Plugin\Subsite\ThemeSubsitePlugin;
use Drupal\subsite\SubsiteManager;

/**
 * Determines the default theme of the site.
 */
class SubsiteNegotiator extends DefaultNegotiator {
  protected $theme;

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    if ($route_match) {
      $theme_node = FALSE;

      /** @var SubsiteManager $subsite_manager */
      $subsite_manager = \Drupal::service('subsite.manager');

      /** @var Node $node */
      if ($node = $route_match->getParameter('node')) {
        $theme_node = $subsite_manager->getSubsiteNode($node);
      }

      if ($theme_node) {
        // This is where we check the theme setting for the book node and apply.
        /** @var ThemeSubsitePlugin $plugin */
        $plugin = $subsite_manager->getPlugin('subsite_theme', $theme_node);
        if ($theme = $plugin->getTheme()) {
          $this->theme = $theme;
          return TRUE;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    if (!empty($this->theme)) {
      return $this->theme;
    }
  }

}
