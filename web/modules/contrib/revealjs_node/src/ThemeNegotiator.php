<?php

namespace Drupal\revealjs_node;

use Drupal\Core\Theme\DefaultNegotiator;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Switch theme when a presentation-node is to be displayed.
 */
class ThemeNegotiator extends DefaultNegotiator {

  /**
   * Theme name for presentations.
   *
   * @var string
   *
   * @todo make it configurable?
   */
  protected $theme = 'revealjs_theme';

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $handled = FALSE;
    $viewMode = 'full';
    switch ($route_match->getRouteName()) {
      case 'entity.node.preview':
        $entity = $route_match->getParameter('node_preview');
        $viewMode = $route_match->getParameter('view_mode_id');
        break;

      case 'entity.node.revision':
        $nid = $route_match->getParameter('node_revision');
        $entity = \Drupal::entityTypeManager()
          ->getStorage('node')
          ->loadRevision($nid);
        break;

      case 'entity.node.canonical':
        $entity = $route_match->getParameter('node');
        break;

      case 'entity.media.canonical':
        $entity = $route_match->getParameter('media');
        break;

      default:
        break;
    }
    if (!empty($entity) && $entity->bundle() == 'reveal_js_presentation' &&  $viewMode == 'full') {
      $handled = TRUE;
    }
    return $handled;
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    return $this->theme;
  }

}
