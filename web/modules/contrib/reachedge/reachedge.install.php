<?php

/**
 * @file
 * Installation file for ReachEdge module.
 */

/**
 * Implements hook_uninstall().
 */
function reachedge_uninstall() {
  $config = \Drupal::config('reachedge.reachedgeconfig');
  $config->set('reachedge_id', '')->save();
}


/**
 * Implements hook_requirements().
 */
function reachedge_requirements($phase) {
  $requirements = array();
  $t = get_t();

  if ($phase == 'runtime') {
    // Raise warning if ReachEdge Site ID has not been set yet.
    if (!preg_match('/^[A-Z0-9]{8}(-[A-Z0-9]{4}){3}-[A-Z0-9]{12}$/i', variable_get('reachedge_id', 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX'))) {
      $requirements['reachedge_id'] = array(
        'title' => $t('ReachEdge module'),
        'description' => $t('ReachEdge module has not been configured yet. Please configure its settings from the <a href="@url">ReachEdge settings page</a>.', array('@url' => url('admin/config/system/reachedge'))),
        'severity' => REQUIREMENT_WARNING,
        'value' => $t('Not configured'),
      );
    }
  }

  return $requirements;
}