<?php

/**
 * @file
 * Documents hooks provided by the ACSF module.
 */

/**
 * Defines ACSF event handlers.
 *
 * See api.md.
 */
function hook_acsf_registry() {
  return [
    'events' => [
      [
        'weight' => -1,
        'type' => 'acsf_install',
        // The below can be just 'YourClassName' if your code does not
        // contain a namespace declaration.
        'class' => '\Drupal\your_module\YourClassName',
        // 'path' is necessary only if the location is non-standard:
        'path' => drupal_get_path('module', 'your_module') . '/classes',
      ],
    ],
  ];
}

/**
 * Modifies the preserved list of user roles for staged sites.
 *
 * Use this hook to protect user accounts with specific role assignments from
 * being scrubbed when you copy production websites to the staging environment.
 * Scrubbed user accounts are assigned anonymous email addresses and have their
 * passwords reset to randomized strings.
 *
 * @param array $admin_roles
 *   An indexed array of integer role IDs - Users with these roles will be
 *   preserved.
 */
function hook_acsf_staging_scrub_admin_roles_alter(array &$admin_roles) {
  if ($role = \Drupal::config('mymodule')->get('admin_role')) {
    $admin_roles[] = $role;
  }
}

/**
 * Modifies the preserved list of user IDs for staged sites.
 *
 * Use this hook to protect specific user accounts from being scrubbed when you
 * copy production websites to the staging environment. Scrubbed user accounts
 * are assigned anonymous email addresses and have their passwords reset to
 * randomized strings.
 *
 * @param array $preserved_uids
 *   An indexed array of integer user IDs to preserve.
 */
function hook_acsf_staging_scrub_preserved_users_alter(array &$preserved_uids) {
  if ($uids = \Drupal::config('mymodule')->get('preserved_uids', [])) {
    $preserved_uids = array_merge($preserved_uids, $uids);
  }
}
