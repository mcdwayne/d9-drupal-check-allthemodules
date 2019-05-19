<?php

/**
 * @file
 * Contains \Drupal\unpublish_own_comment\Controller\UnpublishOwnCommentController.
 */

namespace Drupal\unpublish_own_comment\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class UnpublishOwnCommentController  extends ControllerBase {
  public function form($node, $comment) {
    if (!is_numeric($node) || !is_numeric($comment)) {
      // We will just show a standard "access denied" page in this case.
      throw new AccessDeniedHttpException();
    }

    $list[] = $this->t("Node number was @number.", array('@number' => $node));
    $list[] = $this->t("Second number was @number.", array('@number' => $comment));

    $render_array['page_example_arguments'] = array(
      // The theme function to apply to the #items
      '#theme' => 'item_list',
      // The list itself.
      '#items' => $list,
      '#title' => $this->t('Argument Information'),
    );
    return $render_array;
  }
}
