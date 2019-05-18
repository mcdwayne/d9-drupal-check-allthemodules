<?php

/**
 * @file
 * Implementation of the og_sm hooks.
 */

/**
 * Implements hook_og_sm_admin_menu().
 */
function og_sm_feature_og_sm_admin_menu() {
  $items = array();

  $items['features'] = array(
    'title' => 'Features',
    'description' => 'Manage Site features.',
    'href' => 'admin/features',
    'column' => 'right',
    'weight' => 0,
  );
  $items['features/overview'] = array(
    'title' => 'Manage features',
    'description' => 'Manage Site features.',
    'href' => 'admin/features',
    'weight' => -1000,
    'exclude_menu' => TRUE,
  );

  // Add features to the drop-down menu.
  $features = og_sm_feature_info();
  foreach ($features as $name => $feature) {
    // We only add feature that have a settings page.
    if (empty($feature['site configuration'])) {
      continue;
    }

    $items['features/' . $name] = array(
      'title' => $feature['name'],
      'description' => $feature['description'],
      'href' => $feature['site configuration'],
      'options' => array(
        'og_sm_feature' => $name,
      ),
    );
  }

  // Make items translatable.
  if (FALSE) {
    t('Features');
    t('Manage features');
    t('Manage Site features.');
  }

  return $items;
}

/**
 * Implements hook_og_sm_variable_defaults().
 */
function og_sm_feature_og_sm_variable_defaults($site) {
  $items = array();

  // Add the default features variables.
  $feature_names = array_keys(og_sm_feature_info());
  foreach ($feature_names as $name) {
    $var_key = og_sm_feature_variable_key($name);

    // Skip not enabled features.
    $default = variable_get($var_key, FALSE);
    if (empty($default)) {
      continue;
    }

    $items[$var_key] = $default;
  }

  return $items;
}
