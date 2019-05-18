<?php

/**
 * @file
 * API documentation of available og_sm_site_clone hooks.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Hook to alter the new Site before being shown in the node form.
 *
 * @param object $site_new
 *   The new Site object being prepared.
 * @param array $context
 *   The context for the alter. The context contains:
 *   - original_site: Site node object that is used as source for the clone.
 */
function hook_og_sm_site_clone_object_prepare_alter(&$site_new, array $context) {
  // Set the new title based on original site prefixed with "Clone of".
  $site_original = $context['site_original'];
  $site_new->title = t(
    'Clone of !title',
    array('!title' => $site_original->title)
  );
}

/**
 * Hook triggered when a Site was saved as a clone of another Site.
 *
 * @param object $site_new
 *   The newly created Site node.
 * @param object $site_original
 *   The original Site the newly created Site was cloned from.
 */
function hook_og_sm_site_clone($site_new, $site_original) {
  // Clone a variable from original to new.
  og_sm_variable_set(
    $site_new->nid,
    'variable_name',
    og_sm_variable_get($site_original->nid, 'variable_name')
  );
}

/**
 * @} End of "addtogroup hooks".
 */
