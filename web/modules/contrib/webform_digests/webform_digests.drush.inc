<?php

/**
 * @file
 * Drush commands for Webform Submission Notifications.
 */

/**
 * Implements hook_drush_command().
 */
function webform_digests_drush_command() {
  $items = array();
  $items['queue-digests'] = [
    'description' => 'Queue webform digests to be sent ',
    'drupal dependencies' => [
      'webform_digests',
    ],
  ];
  return $items;
}

/**
 * Drush command callback for queue-digests.
 *
 * @see webform_digests_drush_command()
 */
function drush_webform_digests_queue_digests() {
  $queue_count = \Drupal::service('webform_digests.queue_builder')->queueSubmissions();
  drush_print(dt('@queue digest email(s) queued', ['@queue' => $queue_count]));
}
