<?php

/**
 * @file
 * Site Manager (og_sm) related hooks.
 */

/**
 * Implements hook_og_sm_admin_menu_alter().
 */
function og_sm_user_create_og_sm_admin_menu_alter(array &$items) {
  $items['people/add']['href'] = 'admin/people/add-user/create';
  $items['people/add']['title'] = 'Create account';
}
