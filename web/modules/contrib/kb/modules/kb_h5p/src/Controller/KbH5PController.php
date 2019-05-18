<?php
/**
 * Created by PhpStorm.
 * User: laboratory.mike
 */

namespace Drupal\kb_h5p\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\views\Views;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Access\AccessResult;

/**
 * Provides the route controller for kb_h5p.
 */

class KbH5PController extends ControllerBase {
  /**
   * @inheritdoc
   */
  public function kb_h5p_content(GroupInterface $group) {
    // Todo: add access control
    $access = TRUE;
    if($access) {
      $content = NULL;
      $args = [$group->id()];
      $view = Views::getView('manage_kb_h5p_content');
      if (is_object($view)) {
        $view->setArguments($args);
        $view->setDisplay('embed_1');
        $view->preExecute();
        $view->execute();
        $content = $view->buildRenderable('embed_1', $args);
        return $content;
      }
      else {
        return ['#markup' => 'View error - please confirm that the kb_categories view with embed_1 display mode is available'];
      }
    }
    else {
      throw new AccessDeniedHttpException();
      return AccessResult::forbidden();
    }
  }
}