<?php
/**
 * @file
 * Helper functions for cronpub module.
 */


/**
 * Checks if the Rrule Js library is installed correct.
 *
 * @return bool
 *   Rrule library is installed correct or not.
 */
function cronpub_rrulejs_library_exists() {
  $files = \Drupal::config('cronpub.settings')->get('required_library_files');
  $library_exists = TRUE;
  foreach ($files as $file) {
    if (!file_exists(DRUPAL_ROOT . '/' . $file)) {
      $library_exists = FALSE;
      continue;
    }
  }
  return $library_exists;
}