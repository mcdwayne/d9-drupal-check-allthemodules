<?php

/**
 * @file
 * Drush integration for the default_content module.
 */

/**
 * Implements hook_drush_post_COMMAND().
 */
function drush_default_content_extra_post_default_content_export_references($entity_type_id, $entity_id = NULL) {
  $delete_users = \Drupal::config('default_content_extra.settings')->get('delete_users');
  $folder = drush_get_option('folder', '.');

  // Only delete users if setting is enabled.
  if ($delete_users) {
    // Get a user storage object, load users 0 and 1, iterate.
    $user_storage = \Drupal::entityTypeManager()->getStorage('user');
    $users = $user_storage->loadMultiple([0, 1]);

    foreach ($users as $user) {
      // Set delete path.
      $path = $folder . '/user/' . $user->uuid() . '.json';
      if (file_exists($path)) {
        unlink($path);
        $args = ['@path' => $path, '@user_id' => $user->id()];
        drush_log(dt('Deleted @path for user @user_id.', $args), 'ok');
      }
    }
  }

  // Delete all exported workspaces.
  $dir_path = $folder . '/workspace/';
  if ($dir_path) {
    $files = glob($dir_path . '*', GLOB_MARK);
    foreach ($files as $file) {
      unlink($file);
    }
    rmdir($dir_path);
    drush_log(dt('Deleted @path.', ['@path' => $dir_path]), 'ok');
  }
}
