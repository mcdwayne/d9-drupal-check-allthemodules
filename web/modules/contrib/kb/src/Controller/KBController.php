<?php
/**
 * Created by PhpStorm.
 * User: laboratory.mike
 * Date: 12/9/17
 * Time: 10:25 AM
 */

namespace Drupal\kb\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\views\Views;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Access\AccessResult;

/**
 * Provides the route controller for kb.
 */

class KBController extends ControllerBase {

  /**
   * Creates the settings page
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to build the settings page
   * @return $text;
   *   The page text to return
   */
  public function kb_management(GroupInterface $group) {
    $markup .= '<h3>' . t('KB Categories') . '</h3>';
    $markup .= '<p>' . t('Add and remove categories for organizing content.') . '</p>';
    $markup .= '<h3>' . t('KB Content') . '</h3>';
    $markup .= '<p>' . t('Manage all content for this KB Group.') . '</p>';

    return ['#markup' => $markup];
  }

  /**
   * @inheritdoc
   */
  public function kb_categories(GroupInterface $group) {
    // Todo: add access control
    $access = TRUE;
    if($access) {
      $content = NULL;
      $args = [$group->id()];
      $view = Views::getView('kb_categories');
      if (is_object($view)) {
        $view->setArguments($args);
        $view->setDisplay('embed_1');
        $view->preExecute();
        $view->execute();
        $content = $view->buildRenderable('embed_1', $args);
        //dpm($content);
        return $content;
        //return ['#markup' => 'testing'];
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
  /**
   * @inheritdoc
   */
  public function kb_content(GroupInterface $group) {
    // Todo: add access control
    $access = TRUE;
    if($access) {
      $content = NULL;
      $args = [$group->id()];
      $view = Views::getView('manage_kb_content');
      if (is_object($view)) {
        $view->setArguments($args);
        $view->setDisplay('embed_1');
        $view->preExecute();
        $view->execute();
        $content = $view->buildRenderable('embed_1', $args);
        //dpm($content);
        return $content;
        //return ['#markup' => 'testing'];
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