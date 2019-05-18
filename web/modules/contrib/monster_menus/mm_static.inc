<?php

/**
 * @file
 * A collection of large, static strings--such as Javascript and HTML code--in MM
 */

use \Drupal\Core\Url;
use Drupal\monster_menus\Constants;

/**
 * Add some Javascript/HTML/text to the ['#attached'] portion of a render array.
 *
 * @param &$array
 *   Render array to add the data to.
 * @param $which
 *   Key of the code to add
 * @param ...
 *   Any additional parameters are passed to the underlying function
 */
function mm_static(&$array, $which) {
  $args = func_get_args();
  // Skip $which.
  array_splice($args, 1, 1);
  // While this could be done with an array, that would be less efficient.
  // Instead, require that a function of the format "_mm_static__$which" exists.
  $name = "_mm_static__$which";
  if (!function_exists($name)) {
    // Function doesn't exist, so try to add as a named library.
    mm_add_library($array, $which);
    return;
  }
  // Reset reference to $array.
  $args[0] = &$array;
  call_user_func_array($name, $args);
}

/* Referenced by mm_ui_content_edit */
function _mm_static__edit_cat(&$array) {
  mm_add_library($array, 'edit_cat');
  _mm_static__settings_perms($array);
}

/* Referenced by mm_ui_content_edit, _mm_ui_node_form_perms */
function _mm_static__settings_perms(&$array, $form_id = 'mm-ui-content-edit', $submit_id = 'edit-submit', $row_selector = Constants::MM_PERMS_READ) {
  mm_add_library($array, 'settings_perms');
  mm_add_library($array, 'perms_summary');
  mm_add_js_setting($array, 'settings_perms', [
    'r'                 => Constants::MM_PERMS_READ,
    'w'                 => Constants::MM_PERMS_WRITE,
    'row_selector'      => $row_selector,
    'disabled_selector' => $row_selector == Constants::MM_PERMS_READ ? Constants::MM_PERMS_WRITE : $row_selector,
    'form_id'           => $form_id,
    'submit_id'         => $submit_id,
  ]);
}

/* Referenced by mm_node_redir_form */
function _mm_static__node_redir(&$array) {
  mm_add_library($array, 'node_redir');
}

/* Referenced by mm_ui_content_copymove */
function _mm_static__copymove(&$array, $mustcheck) {
  mm_add_library($array, 'copymove');
  mm_add_js_setting($array, 'copymove', ['mustcheck' => $mustcheck]);
}

/* Referenced by DefaultController::showSolver */
function _mm_static__solver_mm_list_callback(&$array, $mmtid) {
  mm_add_library($array, 'solver_mm_list_callback');
  mm_add_js_setting($array, 'solver_mm_list_callback', ['path' => base_path() . "mm/$mmtid/settings/solver/"]);
}

/* Referenced by _mm_ui_content_edit */
function _mm_static__group_table(&$array, $mmtid, $token, $element_name) {
  mm_add_library($array, 'group_table');

  $columns = _mm_ui_userlist_get_headers();
  $col_def = array_fill(0, count($columns) - 1, NULL);
  $col_def[] = ['bSortable' => FALSE];

  mm_add_js_setting($array, 'group_table', [
    'clean_element' => str_replace('-', '_', $element_name),
    'id_name'       => str_replace('_', '-', $element_name),
    'col_def'       => $col_def,
    'load'          => Url::fromRoute('monster_menus.large_group_get_users_json', ['mm_tree' => $mmtid, 'element' => $element_name, 'token' => $token])->toString(),
    'delete'        => Url::fromRoute('monster_menus.large_group_delete_user',    ['mm_tree' => $mmtid, 'user' => '-USER-', 'token' => $token])->toString(),
    'add'           => Url::fromRoute('monster_menus.large_group_add_users',      ['mm_tree' => $mmtid, 'uids' => '-UIDS-', 'token' => $token])->toString(),
  ]);
}

function _mm_static__lazy_load_node(&$array, $mmtid, $max_pages) {
  mm_add_library($array, 'lazy_load_node');
  mm_add_js_setting($array, 'lazy_load_node', [
    'loading_img'  => drupal_get_path('module', 'monster_menus') . '/images/ajax-loader.gif',
    'mmtid' => $mmtid,
    'max_pages' => $max_pages,
  ]);
}

/* Referenced by DefaultController::showGroup() */
function _mm_static__show_group(&$array, $mmtid, $col_def) {
  mm_add_library($array, 'show_group');
  // Adding ->toString(TRUE) adds cacheability metadata, to avoid an error in
  // EarlyRenderingControllerWrapperSubscriber.
  $link_location = Url::fromRoute('monster_menus.large_group_get_users_json', ['mm_tree' => $mmtid, 'element' => 'mm-user-datatable-members-display'])
    ->toString(TRUE)
    ->getGeneratedUrl();
  mm_add_js_setting($array, 'show_group', [
    'col_def'       => $col_def,
    'link_location' => $link_location,
  ]);
}

function _mm_static__mm_categories_summary(&$array, $offset) {
  mm_add_library($array, 'mm_categories_summary');
  mm_add_js_setting($array, 'mm_categories_summary', ['offset' => $offset]);
}