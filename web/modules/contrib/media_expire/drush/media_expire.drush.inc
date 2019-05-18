<?php

/**
 * @file
 * Drush integration for the media_expire module.
 */

/**
 * Implements hook_drush_command().
 */
function media_expire_drush_command() {
  $items['media-expire-check'] = [
    'description' => dt('Checks for expired media.'),
    'aliases' => ['mec'],
  ];

  return $items;
}

/**
 * Checks for expired media.
 */
function drush_media_expire_check() {

  /** @var \Drupal\media_expire\MediaExpireService $service */
  $service = \Drupal::service('media_expire.service');
  $service->unpublishExpiredMedia();
}
