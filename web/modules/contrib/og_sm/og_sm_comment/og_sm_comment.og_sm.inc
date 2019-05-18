<?php

/**
 * @file
 * Site Manager (og_sm) related hooks.
 */

/**
 * Implements hook_og_sm_admin_menu().
 */
function og_sm_comment_og_sm_admin_menu() {
  $items = array();

  $items['content/comments'] = array(
    'title' => 'Administer comments',
    'description' => 'Manage the comments of this Site.',
    'href' => 'admin/comments',
    'weight' => -5,
  );

  // Make items translatable.
  t('Administer comments');
  t('Manage the comments of this Site.');

  return $items;
}

/**
 * Implements hook_og_sm_content_type_info_alter().
 */
function og_sm_comment_og_sm_content_type_info_alter(&$type_info, $site) {
  $type_info->comment_config = new OgSmCommentOverrideConfig(
    variable_get('comment_' . $type_info->type, COMMENT_NODE_OPEN),
    og_sm_variable_get(
      $site->nid,
      sprintf('og_sm_content_%s_comment', $type_info->type)
    ),
    og_sm_variable_get(
      $site->nid,
      sprintf('og_sm_content_%s_comment_override', $type_info->type),
      FALSE
    ),
    og_sm_variable_get(
      $site->nid,
      sprintf('og_sm_content_%s_comment_override_default', $type_info->type)
    )
  );
}
