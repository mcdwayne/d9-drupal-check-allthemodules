<?php

function groundhog_day_drush_command() {
  $commands = [
    'groundhog-day-update' => [
      'aliases' => ['ghdu'],
    ],
    'groundhog-day-snapshot' => [
      'aliases' => ['ghds'],
    ],
    'groundhog-day-reset' => [
      'aliases' => ['ghdr'],
    ],
  ];
  return $commands;
}

function drush_groundhog_day_update() {
  \Drupal::service('groundhog_day.executor')->update();
}

function drush_groundhog_day_reset() {
  \Drupal::service('groundhog_day.executor')->reset();
}

function drush_groundhog_day_snapshot() {
  \Drupal::service('groundhog_day.executor')->snapshot();
}
