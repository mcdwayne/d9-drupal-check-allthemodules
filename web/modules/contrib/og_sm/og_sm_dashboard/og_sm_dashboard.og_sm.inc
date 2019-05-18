<?php

/**
 * @file
 * Implementation of the og_sm hooks.
 */

/**
 * Implements hook_og_sm_platform_administration_page_alter().
 */
function og_sm_dashboard_og_sm_platform_administration_page_alter(&$path) {
  $path = 'admin/dashboard';
}
