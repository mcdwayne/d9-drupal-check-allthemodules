<?php

namespace Drupal\rac\Plugin\adva\AccessProvider;

use Drupal\adva\Plugin\adva\ReferenceAccessProvider;

use Drupal\Core\Session\AccountInterface;

/**
 * Role Access Provider for Advanced Access.
 *
 * Implements access control using reference fields with a target type of
 * user_role.
 *
 * View grants are given to each of the roles referenced from a entity. Update
 * grants are determined by the update permissions.
 *
 * @AccessProvider(
 *   id = "rac",
 *   label = @Translation("Role Access Control"),
 *   operations = {
 *     "view",
 *     "update",
 *     "delete",
 *   },
 * )
 */
class RoleAccessProvider extends ReferenceAccessProvider {

  /**
   * {@inheritdoc}
   */
  public static function getTargetType() {
    return "user_role";
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthorizedEntityIds($operation, AccountInterface $account) {
    // A user should be authorized to access content for any roles they have.
    return $account->getRoles();
  }

}
