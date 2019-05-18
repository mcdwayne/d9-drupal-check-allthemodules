<?php

/**
 * @file
 * Implemented og_sm hooks.
 */

/**
 * Implements hook_og_sm_admin_menu().
 */
function og_sm_site_clone_og_sm_admin_menu() {
  $items = array();

  $items['structure/site-clone'] = array(
    'title' => 'Clone Site',
    'description' => 'Create a new Site based on this one.',
    'href' => 'admin/site-clone',
    'weight' => 0,
  );

  // Translate strings.
  t('Clone Site');
  t('Create a new Site based on this one.');

  return $items;
}

/**
 * Implements hook_og_sm_site_insert().
 */
function og_sm_site_clone_og_sm_site_insert($site) {
  // Trigger clone hook when the newly inserted site is a clone of an other.
  if (empty($site->site_is_clone) || empty($site->site_is_clone_of_nid)) {
    return;
  }

  $original_site = og_sm_site_load($site->site_is_clone_of_nid);
  module_invoke_all('og_sm_site_clone', $site, $original_site);

  // Clear Site cache and schedule a menu rebuild.
  og_sm_site_cache_clear_all($site);
  menu_rebuild();
}
