<?php

namespace Drupal\phones_contact\Hook;

/**
 * @file
 * Contains \Drupal\app\Controller\AjaxResult.
 */

use Drupal\Core\Controller\ControllerBase;
use Drupal\phones_call\Controller\ViewsCalls;
use Drupal\phones_contact\Controller\ViewsContacts;
use Drupal\phones_contact\Controller\ContactPhones;

/**
 * Controller routines for page example routes.
 */
class View extends ControllerBase {

  /**
   * Page Callback.
   */
  public static function hook(&$build, $entity, $view_mode) {
    if (self::checkType($entity) && $type = self::checkBundle($entity)) {
      if ($view_mode == 'full') {
        $phones = ContactPhones::getPhones($entity);
        if ($type == 'organization') {
          if (!empty($view = ViewsContacts::getRenderable($entity))) {
            $build['persons'] = [
              '#weight' => '60',
              'header' => ['#markup' => '<h3>Контакты</h3>'],
              'views' => $view,
            ];
          }
        }
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
      if ($entity->getEntityTypeId() == 'phones_contact') {
        $result = TRUE;
      }
    }
    return $result;
  }

  /**
   * Check bundle.
   */
  public static function checkBundle($entity) {
    $result = FALSE;
    if (method_exists($entity, 'bundle')) {
      $type = $entity->bundle();
      if (in_array($type, ['person', 'organization'])) {
        $result = $type;
      }
    }
    return $result;
  }

}
