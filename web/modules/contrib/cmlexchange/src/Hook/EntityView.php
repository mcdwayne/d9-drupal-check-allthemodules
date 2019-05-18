<?php

namespace Drupal\cmlexchange\Hook;

/**
 * @file
 * Contains \Drupal\cmlexchange\Controller\EntityView.
 */

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for page example routes.
 */
class EntityView extends ControllerBase {

  /**
   * Page Callback.
   */
  public static function hook(&$build, $entity, $view_mode) {
    if (self::check($entity)) {
      if ($view_mode == 'full') {
        $id = $entity->id();
        $build['cml'] = [
          '#weight' => -10,
          'import' => \Drupal::formBuilder()->getForm('Drupal\cmlexchange\Form\Import', $id),
        ];
      }
    }
  }

  /**
   * Check node.
   */
  public static function check($entity) {
    $result = FALSE;
    if (method_exists($entity, 'getEntityTypeId')) {
      $type = $entity->getEntityTypeId();
      if (in_array($type, ['cml'])) {
        $result = TRUE;
      }
    }
    return $result;
  }

}
