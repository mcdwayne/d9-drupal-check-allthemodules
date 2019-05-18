<?php

namespace Drupal\phones_contact\Hook;

/**
 * @file
 * Contains \Drupal\app\Controller\AjaxResult.
 */

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for page example routes.
 */
class Preprocess extends ControllerBase {

  /**
   * Page Callback.
   */
  public static function hook(&$variables) {
    if ($entity = self::check($variables)) {

    }
  }

  /**
   * Check node.
   */
  public static function check($variables) {
    $result = FALSE;
    $entity = $variables['node'];
    if (is_object($entity) && method_exists($entity, 'getType')) {
      $type = $entity->getType();
      //if (in_array($type, ['cntx'])) {
      //  $result = $entity;
      //}
    }
    return $result;
  }

}
