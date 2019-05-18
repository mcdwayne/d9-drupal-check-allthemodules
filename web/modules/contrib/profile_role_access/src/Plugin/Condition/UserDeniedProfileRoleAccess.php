<?php

namespace Drupal\profile_role_access\Plugin\Condition;

use Drupal\rules\Core\RulesConditionBase;
use Drupal\user\UserInterface;

/**
 * Provides a 'User Denied Profile Role Access' condition.
 *
 * @Condition(
 *   id = "rules_user_has_profile_role_access",
 *   label = @Translation("User denied profile role access"),
 *   category = @Translation("User"),
 *   context = {
 *     "account" = @ContextDefinition("entity:user",
 *       label = @Translation("Account")
 *     ),
 *     "user" = @ContextDefinition("entity:user",
 *       label = @Translation("User")
 *     ),
 *   }
 * )
 *
 */
class UserDeniedProfileRoleAccess extends RulesConditionBase {

  /**
   * Check if user is denied access to viewed profile.
   *
   * @param \Drupal\user\UserInterface $account
   *   The account to check.
   *
   * @param \Drupal\user\UserInterface $entity
   *   The current user account
   *
   * @return bool
   *   TRUE if access is denied.
   */
  protected function doEvaluate(UserInterface $entity, UserInterface $account) {

    // Only check if not viewing own profile (access control is ignored).
    if ($entity->id() != $account->id()) {

      $matrix = \Drupal::config('profile_role_access.settings')->get('access_matrix');

      // Only check if the matrix is valid.
      if ((is_array($matrix)) && (count($matrix) > 0)) {

        foreach ($account->getRoles() as $currentrole) {
          foreach ($entity->getRoles() as $viewedrole) {
            if (isset($matrix[$currentrole][$viewedrole]) &&
              ($matrix[$currentrole][$viewedrole] == 1)) {
              return false;
            }
          }
        }

        // Deny view access.
        return true;
      }
    }

    return false;
  }

}
