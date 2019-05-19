<?php

namespace Drupal\webform_prepopulate\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\webform\WebformInterface;

/**
 * Defines the custom access control handler for the webform prepopulate UI.
 */
class WebformPrepopulateAccess {

  /**
   * Check that webform prepopulate can be edited by a user.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function checkWebformPrepopulateAccess(WebformInterface $webform, AccountInterface $account) {
    return $webform->access('update', $account, TRUE)
      ->andIf(AccessResult::allowedIfHasPermission($account, 'edit webform prepopulate'));
  }

}
