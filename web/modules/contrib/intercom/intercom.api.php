<?php

/**
 * @file
 * Hooks specific to the Intercom module.
 */

/**
 * Alter the parameters used by intercom_user_parameters() for User sync.
 *
 * @param array $parameters
 *   An array containing all of parameters for Intercom User integration.
 */
function hook_intercom_user_parameters_alter(array &$parameters) {
  /** @var \Drupal\user\Entity\User $entity */
  $current_user = \Drupal::currentUser();
  /** @var \Drupal\user\Entity\User $account */
  $account = \Drupal\user\Entity\User::load($current_user->id());
  // Add the Intercom Id stored in the user data.
  $intercom_id = \Drupal::service('user.data')->get('intercom', $account->id(), 'intercom_id');
  if ($intercom_id !== NULL) {
    $parameters['id'] = $intercom_id;
  }
}
