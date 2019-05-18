<?php

namespace Drupal\contact_form_permissions\Access;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Determines access to the contact form delete page.
 *
 * @package Drupal\contact_form_permissions\Access
 */
class ContactDeleteAccessCheck extends ContactAccessCheckBase {

  /**
   * Checks access.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return string
   *   A \Drupal\Core\Access\AccessInterface constant value.
   */
  public function access(AccountInterface $account, RouteMatchInterface $route_match) {
    return $this->accessByOperation('delete', $account, $route_match);
  }

}
