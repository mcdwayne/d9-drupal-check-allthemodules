<?php

namespace Drupal\concurrent_users_notification\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access check for concurrent_users_notification routes.
 */
class ConcurrentUsersNotificationAccessCheck implements AccessInterface {

  /**
   * Checks access.
   *
   * @param string $key
   *   The concurrent_users_notification key.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access($key) {
    if ($key != \Drupal::state()->get('concurrent_users_notification.cun_key')) {
      \Drupal::logger('concurrent_users_notification')->notice('Service could not run because an invalid key was used.');
      return AccessResult::forbidden()->setCacheMaxAge(0);
    }
    return AccessResult::allowed()->setCacheMaxAge(0);
  }

}
