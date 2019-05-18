<?php

/**
 * @file
 * Implements Backround Proces Pages.
 */

/**
 * Implements to Callback for token validation.
 */
function background_process_check_token() {
  header("Content-Type: text/plain");
  print \Drupal::config('background_process.settings')->get('background_process_token');
  exit;
}
