<?php

namespace Drupal\phones_call\Hook;

/**
 * @file
 * Contains \Drupal\app\Controller\AjaxResult.
 */

use Drupal\Core\Controller\ControllerBase;
use Drupal\phones_call\Controller\ViewsCalls;

/**
 * Controller routines for page example routes.
 */
class View extends ControllerBase {

  /**
   * Page Callback.
   */
  public static function hook(&$build, $entity, $view_mode) {
    if (self::checkType($entity)) {
      if ($view_mode == 'full') {
        $phones = [$entity->client->value];
        if (!empty($calls = ViewsCalls::getRenderable($phones))) {
          $build['calls'] = [
            '#weight' => '70',
            'header' => ['#markup' => '<h3>Звонки</h3>'],
            'views' => $calls,
          ];
        }
      }
    }
  }

  /**
   * Check Entity Type Id.
   */
  public static function checkType($entity) {
    $result = FALSE;
    if (method_exists($entity, 'getEntityTypeId')) {
      if ($entity->getEntityTypeId() == 'phones_call') {
        $result = TRUE;
      }
    }
    return $result;
  }

}
