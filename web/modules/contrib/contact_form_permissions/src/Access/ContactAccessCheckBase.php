<?php

namespace Drupal\contact_form_permissions\Access;

use Drupal\contact\ContactFormInterface;
use Drupal\contact_form_permissions\ContactPermissions;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Class ContactAccessCheckBase.
 *
 * @package Drupal\contact_form_permissions\Access
 */
class ContactAccessCheckBase implements AccessInterface {

  /**
   * The contact form permissions.
   *
   * @var \Drupal\contact_form_permissions\ContactPermissions
   */
  protected $contactPermissions;

  /**
   * ContactAccessCheckBase constructor.
   *
   * @param \Drupal\contact_form_permissions\ContactPermissions $contact_permissions
   *   The contact form permissions.
   */
  public function __construct(ContactPermissions $contact_permissions) {
    $this->contactPermissions = $contact_permissions;
  }

  /**
   * Checks access to a contact form according to an operation.
   *
   * @param string $operation
   *   The operation.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return string
   *   A \Drupal\Core\Access\AccessInterface constant value.
   */
  protected function accessByOperation($operation, AccountInterface $account, RouteMatchInterface $route_match) {
    $contact_form = $route_match->getParameter('contact_form');

    if ($contact_form instanceof ContactFormInterface) {
      $permission = $this->contactPermissions->getPermissionKey($operation, $contact_form);
      return AccessResult::allowedIfHasPermission($account, $permission);
    }

    // No opinion.
    return AccessResult::neutral();
  }

}
