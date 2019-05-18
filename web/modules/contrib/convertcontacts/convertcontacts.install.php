<?php

/**
 * @file
 * Installation file for ConvertContacts module.
 */

/**
 * Implements hook_uninstall().
 */
function convertcontacts_uninstall() {
  $config = \Drupal::config('convertcontacts.convertcontactsconfig');
  $config->set('convertcontacts_id', '')->save();
}


/**
 * Implements hook_requirements().
 */
function convertcontacts_requirements($phase) {
  $requirements = array();
  $t = get_t();

  if ($phase == 'runtime') {
    // Raise warning if ConvertContacts Site ID has not been set yet.
    if (!preg_match('/^[A-Z0-9]{8}(-[A-Z0-9]{4}){3}-[A-Z0-9]{12}$/i', variable_get('convertcontacts_id', 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX'))) {
      $requirements['convertcontacts_id'] = array(
        'title' => $t('ConvertContacts module'),
        'description' => $t('ConvertContacts module has not been configured yet. Please configure its settings from the <a href="@url">ConvertContacts settings page</a>.', array('@url' => url('admin/config/system/convertcontacts'))),
        'severity' => REQUIREMENT_WARNING,
        'value' => $t('Not configured'),
      );
    }
  }

  return $requirements;
}