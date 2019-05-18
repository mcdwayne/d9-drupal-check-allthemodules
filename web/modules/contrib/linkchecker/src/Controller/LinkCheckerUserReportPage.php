<?php

namespace Drupal\linkchecker\Controller;

use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Builds user broken link report page.
 */
class LinkCheckerUserReportPage {

  /**
   * @return string
   */
  public function content() {
    return '@TODO';
  }

  /**
   * Checks access for a specific request.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   */
  public function access(AccountInterface $account) {
    $user = \Drupal::currentUser();

    // Users with 'access own broken links report' permission can only view
    // their own report. Users with the 'access broken links report' permission
    // can view the report for any authenticated user.
    return AccessResult::allowedIf($account->id() && (($user->id()) == $account->id()) && $account->hasPermission('access own broken links report') || $account->hasPermission('access broken links report'));
  }

}
