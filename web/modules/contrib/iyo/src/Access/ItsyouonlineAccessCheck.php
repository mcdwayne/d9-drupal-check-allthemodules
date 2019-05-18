<?php

namespace Drupal\itsyouonline\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\Access\AccessInterface;

/**
 * Checks access.
 */
class ItsyouonlineAccessCheck implements AccessInterface {

  /**
   * Access check for Itsyouonline module open urls.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account) {
    $config = \Drupal::config('itsyouonline.account');
    $is_enabled = $config->get('enabled');

    return AccessResult::allowedIf($is_enabled == TRUE);
  }

}
