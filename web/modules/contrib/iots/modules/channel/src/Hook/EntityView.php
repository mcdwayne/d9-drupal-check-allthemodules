<?php

namespace Drupal\iots_channel\Hook;

/**
 * @file
 * Contains \Drupal\iots_channel\Hook\EntityView.
 */

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller EntityView.
 */
class EntityView extends ControllerBase {

  /**
   * Hook.
   */
  public static function hook(&$build, $entity, $view_mode) {
    $types = [
      'iots_channel',
    ];
    if (self::checkType($entity, $types)) {
      if ($view_mode == 'full') {
        drupal_set_message(__FILE__, 'warning');
        if (FALSE) {
          $form = 'Drupal\eexamples\Form\ChangeStatus';
          $build['custom'] = [
            'from' => \Drupal::formBuilder()->getForm($form, $node),
          ];
        }
      }
    }
  }

  /**
   * Check Entity Type Id.
   */
  public static function checkType($entity, $types) {
    $result = FALSE;
    if (method_exists($entity, 'getEntityTypeId')) {
      $type = $entity->getEntityTypeId();
      if (in_array($type, $types)) {
        $result = $type;
      }
    }
    return $result;
  }

}
