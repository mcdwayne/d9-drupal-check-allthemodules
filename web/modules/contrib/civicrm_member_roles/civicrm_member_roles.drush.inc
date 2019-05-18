<?php

/**
 * @file
 * CiviMember Roles Sync drush integration.
 */

/**
 * Implements hook_drush_command().
 */
function civicrm_member_roles_drush_command() {
  $items['civicrm-member-role-sync'] = [
    'description' => 'Run CiviCRM Member Role Sync',
    'examples' => [
      'drush civicrm-member-role-sync --uid=8',
      'drush civicrm-member-role-sync --contact_id=89',
    ],
    'options' => [
      'uid' => 'Optional User ID',
      'contact_id' => 'Optional Contact ID',
    ],
    'aliases' => ['cmrs'],
  ];
  return $items;
}

/**
 * Drush callback for civicrm-member-role-sync.
 */
function drush_civicrm_member_roles_civicrm_member_role_sync() {
  /** @var \Drupal\civicrm_member_roles\CivicrmMemberRoles $civicrm_member_roles */
  $civicrm_member_roles = \Drupal::service('civicrm_member_roles');

  if ($uid = drush_get_option('uid')) {
    $storage = \Drupal::entityTypeManager()->getStorage('user');
    if (!$account = $storage->load($uid)) {
      return drush_log(dt('Unable to load user ID @uid.', ['@uid' => $uid]), 'error');
    }
    $civicrm_member_roles->syncUser($account);
    return drush_log(dt('Successfully synced user ID @uid.', ['@uid' => $uid]), 'ok');
  }
  elseif ($contact_id = drush_get_option('contact_id')) {
    if (!$account = $civicrm_member_roles->getContactAccount($contact_id)) {
      return drush_log(dt('Unable to load user for contact ID @cid.', ['@cid' => $contact_id]), 'error');
    }
    $civicrm_member_roles->syncContact($contact_id, $account);
    return drush_log(dt('Successfully synced contact ID @cid.', ['@cid' => $contact_id]), 'ok');
  }

  $batch = \Drupal::service('civicrm_member_roles.batch.sync')->getBatch();
  $batch['progressive'] = FALSE;
  batch_set($batch);
  drush_backend_batch_process();
}
