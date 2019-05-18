<?php
/**
 * @file
 * Implemented og_sm hooks.
 */

/**
 * Implements hook_og_sm_feature_info().
 */
function og_sm_breadcrumb_og_sm_feature_info() {
  $items = array();

  $items[OG_SM_BREADCRUMB_FEATURE] = array(
    'name' => t('Breadcrumb'),
    'description' => t('Show customizable breadcrumb.'),
    'global configuration' => 'admin/config/group/features/breadcrumb',
    'site configuration' => 'admin/features/breadcrumb',
  );

  return $items;
}

/**
 * Implements hook_og_sm_variable_defaults().
 */
function og_sm_breadcrumb_og_sm_variable_defaults($site) {
  $items = array();

  // Enabled Site breadcrumb trail.
  $items['og_sm_breadcrumb_hide_on_frontpage'] = variable_get('og_sm_breadcrumb_hide_on_frontpage', 1);
  $items['og_sm_breadcrumb_force_home'] = variable_get('og_sm_breadcrumb_force_home', 1);
  $items['og_sm_breadcrumb_append_title'] = variable_get('og_sm_breadcrumb_append_title', 1);

  return $items;
}

/**
 * Implements hook_og_sm_site_clone().
 */
function og_sm_breadcrumb_og_sm_site_clone($site_new, $site_original) {
  // Enable the feature if original is enabled.
  if (og_sm_feature_site_is_enabled($site_original, OG_SM_BREADCRUMB_FEATURE)) {
    og_sm_feature_site_enable($site_new, OG_SM_BREADCRUMB_FEATURE);
  }
  else {
    og_sm_feature_site_disable($site_new, OG_SM_BREADCRUMB_FEATURE);
  }

  // Copy the feature variables.
  og_sm_variable_copy_from_to_by_prefix(
    $site_original->nid,
    $site_new->nid,
    'og_sm_breadcrumb_'
  );
}
