<?php

/**
 * @file
 * Describe hooks provided by the LinkedIn OAuth module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter created user object, before save.
 */
function hook_linkedin_oauth_create_user_alter(Drupal\user\Entity\User $user, array $userinfo) {
  $user->name->value = $userinfo['emailAddress'];
}

/**
 * Alter which fields should be fetched from linkedin.
 */
function hook_linkedin_oauth_userinfo_fields_alter(array &$userinfo_fields) {
  $userinfo_fields = array(
    'id',
    'first-name',
    'last-name',
    'maiden-name',
    'formatted-name',
    'email-address',
    'location',
    'picture-url',
    'public-profile-url',
  );
}

/**
 * @} End of "addtogroup hooks".
 */
