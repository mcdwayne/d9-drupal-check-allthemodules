<?php

use Drupal\system\Entity\Action;

/**
 * @file
 * Post update functions for Profile.
 */

/**
 * Change the plugin ID of the delete action.
 */
function profile_post_update_change_delete_action_plugin() {
  $action_storage = \Drupal::entityTypeManager()->getStorage('action');
  $action = $action_storage->load('profile_delete_action');
  if ($action instanceof Action) {
    $action->setPlugin('entity:delete_action:profile');
    $action->save();
  }
}

/**
 * Change the plugin IDs of the publish and unpublish actions.
 */
function profile_post_update_change_publish_action_plugins() {
  $action_storage = \Drupal::entityTypeManager()->getStorage('action');

  $publish_action = $action_storage->load('profile_publish_action');
  if ($publish_action instanceof Action) {
    $publish_action->setPlugin('entity:publish_action:profile');
    $publish_action->save();
  }
  $unpublish_action = $action_storage->load('profile_unpublish_action');
  if ($unpublish_action instanceof Action) {
    $unpublish_action->setPlugin('entity:unpublish_action:profile');
    $unpublish_action->save();
  }
}
