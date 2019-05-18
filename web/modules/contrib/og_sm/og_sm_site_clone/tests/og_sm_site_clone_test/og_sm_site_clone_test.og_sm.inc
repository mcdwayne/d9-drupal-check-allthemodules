<?php

/**
 * @file
 * Hooks implemented to test the Site Clone functionality.
 */

/**
 * Implements hook_og_sm_site_clone_object_prepare_alter().
 */
function og_sm_site_clone_test_og_sm_site_clone_object_prepare_alter(&$site_new, array $context) {
  // Set the new title based on original site prefixed with "Clone of".
  $site_original = $context['site_original'];
  $site_new->extra_field_to_test = sprintf('Test Hook %d', $site_original->nid);
}

/**
 * Implements hook_og_sm_site_clone().
 */
function og_sm_site_clone_test_og_sm_site_clone($site_new, $site_original) {
  // Set a test variable so we can see that the hook was triggered.
  og_sm_variable_set($site_new->nid, 'test_site_is_copy_of', $site_original->nid);
}
