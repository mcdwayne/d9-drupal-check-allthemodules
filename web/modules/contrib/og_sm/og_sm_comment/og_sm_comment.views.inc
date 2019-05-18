<?php

/**
 * @file
 * Views plugins & hooks.
 */

/**
 * Implements hook_views_data_alter().
 */
function og_sm_comment_views_data_alter(&$data) {
  if (isset($data['comment']['delete_comment'])) {
    $data['comment']['delete_comment']['field']['handler'] = 'og_sm_comment_handler_field_comment_link_delete';
  }
  if (isset($data['comment']['edit_comment'])) {
    $data['comment']['edit_comment']['field']['handler'] = 'og_sm_comment_handler_field_comment_link_edit';
  }
}
