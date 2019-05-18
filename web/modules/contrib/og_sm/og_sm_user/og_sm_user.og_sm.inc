<?php
/**
 * @file
 * Site Manager (og_sm) related hooks.
 */

/**
 * Implements hook_og_sm_user_profile_info().
 */
function og_sm_user_og_sm_user_profile_info() {
  return array(
    'basic_info' => array(
      'render callback' => 'og_sm_user_section_basic_info',
      'weight' => -10,
    ),
  );
}

/**
 * Implements hook_og_sm_feature_info().
 */
function og_sm_user_og_sm_feature_info() {
  $items = array();

  $items[OG_SM_USER_FEATURE_PROFILE] = array(
    'name' => t('User profile'),
    'description' => t('User profile within a site.'),
    'global configuration' => 'admin/config/group/features/user',
    'site configuration' => 'admin/features/user',
  );

  return $items;
}

/**
 * Implements hook_og_sm_site_clone().
 */
function og_sm_user_og_sm_site_clone($site_new, $site_original) {
  // Enable the feature if original is enabled.
  if (og_sm_feature_site_is_enabled($site_original, OG_SM_USER_FEATURE_PROFILE)) {
    og_sm_feature_site_enable($site_new, OG_SM_USER_FEATURE_PROFILE);
  }
  else {
    og_sm_feature_site_disable($site_new, OG_SM_USER_FEATURE_PROFILE);
  }
}

/**
 * Implements hook_og_sm_variable_defaults().
 */
function og_sm_user_og_sm_variable_defaults($site) {
  $items = array();

  $items['og_sm_user_profile_editable'] = variable_get('og_sm_user_profile_editable', TRUE);

  return $items;
}
