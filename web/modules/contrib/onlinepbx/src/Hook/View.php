<?php

namespace Drupal\onlinepbx\Hook;

/**
 * @file
 * Contains \Drupal\app\Controller\AjaxResult.
 */

use Drupal\Core\Controller\ControllerBase;

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
        $uuid = $entity->uuid->value;
        $start_voice = $entity->duration->value - $entity->billsec->value;
        $audio = "<a href='/onlinepbx/record/{$uuid}/rec.mp3' id='call-$uuid'
        data-uuid='$uuid' data-start='$start_voice' class='call-record'>запись</a>";
        $build['record'] = [
          '#weight' => -2,
          '#markup' => $audio,
          '#allowed_tags' => ['audio', 'source', 'span', 'a'],
          '#attached' => [
            'library' => [
              'onlinepbx/table.record',
            ],
          ],
        ];
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
