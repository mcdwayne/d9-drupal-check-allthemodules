<?php

/**
 * Implements hook_drush_command
 */
function release_tracker_drush_command() {
  $items = [];

  $items['release-tracker-bump'] = [
    'description' => "Bumps the release number.",
    'arguments' => [
      'type' => 'The type of release, should be one of major. minor or patch.',
    ],
    'examples' => [
      'drush release-tracker-bump minor' => 'Bump the release one minor version, ie. 8.3.1 to 8.4.0',
    ],
    'aliases' => ['rtb'],
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_CONFIGURATION,
  ];

  $items['release-tracker-set'] = [
    'description' => "Sets the release number.",
    'arguments' => [
      'number' => 'The number of the release.',
    ],
    'examples' => [
      'drush release-tracker-set 1.2.3' => 'Set the release to 1.2.3',
    ],
    'aliases' => ['rts'],
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_CONFIGURATION,
  ];

  return $items;
}

/**
 * Bumps the release tracker setting.
 *
 * @param string $type
 */
function drush_release_tracker_bump($type = 'patch') {
  /** @var \Drupal\release_tracker\ReleaseTrackerInterface $release_tracker */
  $release_tracker = \Drupal::service('release_tracker.release_tracker');
  try {
    $release_tracker->bump($type);
  }
  catch (\InvalidArgumentException $exception) {
    drush_log('Failed to set release number.', 'error');
  }
  drush_log(dt('Release set to !release', ['!release' => $release_tracker->getCurrentRelease()]), 'ok');
}

/**
 * Sets the release tracker number.
 *
 * @param string $number
 */
function drush_release_tracker_set($number) {
  /** @var \Drupal\release_tracker\ReleaseTrackerInterface $release_tracker */
  $release_tracker = \Drupal::service('release_tracker.release_tracker');
  try {
    $release_tracker->setReleaseNumber($number);
  }
  catch (\InvalidArgumentException $exception) {
    drush_log('Failed to set release number.', 'error');
  }
  drush_log(dt('Release set to !release', ['!release' => $release_tracker->getCurrentRelease()]), 'ok');
}
