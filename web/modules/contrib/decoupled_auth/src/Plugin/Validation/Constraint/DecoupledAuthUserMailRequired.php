<?php

namespace Drupal\decoupled_auth\Plugin\Validation\Constraint;

use Drupal\user\Plugin\Validation\Constraint\UserMailRequired;

/**
 * Checks if the user's email address is provided if required.
 *
 * The user mail field is NOT required if account originally had no mail set
 * and the user performing the edit has 'administer users' permission.
 * This allows users without email address to be edited and deleted.
 *
 * @Constraint(
 *   id = "DecoupledAuthUserMailRequired",
 *   label = @Translation("User email required", context = "Validation")
 * )
 */
class DecoupledAuthUserMailRequired extends UserMailRequired {}
