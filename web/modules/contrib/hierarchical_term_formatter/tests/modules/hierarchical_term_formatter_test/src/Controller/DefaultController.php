<?php

namespace Drupal\hierarchical_term_formatter_test\Controller;

use Drupal\node\NodeInterface;
use Drupal\node\Controller\NodeViewController;
use Drupal\Core\Entity\EntityDisplayModeInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller used for previewing nodes in a view mode.
 */
class DefaultController extends NodeViewController {

  /**
   * Provides a page to render a single entity.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to be rendered.
   * @param \Drupal\Core\Entity\EntityDisplayModeInterface $view_mode
   *   The view mode that should be used to display the entity.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function preview(NodeInterface $node, EntityDisplayModeInterface $view_mode) {
    if ($view_mode->getTargetType() == 'node') {
      preg_match('/\.(.*)/', $view_mode->id(), $matches);
      return parent::view($node, $matches[1]);
    }
    throw new NotFoundHttpException();
  }

}
