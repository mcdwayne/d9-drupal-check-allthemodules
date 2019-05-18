<?php

/**
 * @file
 * New Relic RPM Drush integration.
 */

/**
 * Implements hook_drush_init().
 */
function new_relic_rpm_drush_init() {
  if (\Drupal::hasService('new_relic_rpm.adapter')) {
    /** @var \Drupal\new_relic_rpm\ExtensionAdapter\NewRelicAdapterInterface $adapter */
    $adapter = \Drupal::service('new_relic_rpm.adapter');

    // Add custom parameter, telling the drush command executed.
    $command = drush_get_command();
    $drush_command = array_merge([$command['command']], $command['arguments']);
    $adapter->addCustomParameter('Drush command', implode(' ', $drush_command));

    // Set job state.
    $adapter->setTransactionState(\Drupal::config('new_relic_rpm.settings')->get('track_drush'));
  }
}

/**
 * Implements hook_drush_command().
 */
function new_relic_rpm_drush_command() {
  $items = [];

  $items['newrelic-deploy'] = [
    'callback' => '_new_relic_rpm_deploy',
    'description' => dt('Notify New Relic of a deployment.'),
    'arguments' => [
      'user' => dt('Optional. User doing the deploy.'),
      'description' => dt('Optional. A brief description of the deployment.'),
      'changelog' => dt('Optional. A list of changes for this deployment.'),
      'revision' => dt('Optional. Revision id of the deployment.'),
    ],
    'aliases' => ['nrd'],
  ];

  return $items;
}
