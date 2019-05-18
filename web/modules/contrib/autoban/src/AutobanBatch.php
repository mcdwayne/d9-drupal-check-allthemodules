<?php

namespace Drupal\autoban;

/**
 * @file
 * Contains \Drupal\autoban\AutobanBatch.
 */

use Drupal\autoban\Controller\AutobanController;

/**
 * Class AutobanBatch.
 *
 * Provides the batch operations.
 *
 * @ingroup autoban
 */
class AutobanBatch {

  /**
   * IP ban.
   */
  public static function ipBan($rule, &$context) {
    $controller = new AutobanController();
    $banned_ip = $controller->getBannedIP($rule);
    $banned = $controller->banIpList($banned_ip, $rule);

    $context['message'] = t('rule %rule', ['%rule' => $rule]);
    $context['results'] = $banned;
  }

  /**
   * IP ban finished.
   */
  public static function ipBanFinished($success, $results, $operations) {
    if ($success) {
      $message = t('IP bans was finished. Total banned: %results', ['%results' => $results]);
      drupal_set_message($message);
      \Drupal::logger('autoban')->notice($message);
    }
  }

}
