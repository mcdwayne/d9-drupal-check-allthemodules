<?php

/**
 * @file
 * Provides Configuration Management commands.
 */

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Implements hook_drush_command().
 */
function nimbus_drush_command() {

  $items['nimbus-cex'] = [
    'description' => 'Demonstrate how Drush commands work.',
    'aliases' => ['ncex'],
    'callback' => 'nimbus_drush_export',
  ];

  $items['nimbus-cim'] = [
    'description' => 'Wrapper for drush cim.',
    'aliases' => ['ncim'],
    'callback' => 'nimbus_drush_import',
  ];

  $items['nimbus-force-uuid'] = [
    'description' => 'Update uuids.',
    'aliases' => ['fuuid'],
    'callback' => 'nimbus_override_ids',
  ];

  return $items;
}

/**
 * Implements hook_drush_command_alter().
 */
function nimbus_drush_command_alter(&$command) {
  if (isset($command['aliases'][0]) && $command['aliases'][0] == 'cim') {
    $command['callback'] = 'nimbus_drush_import';
  }
  if (isset($command['aliases'][0]) && $command['aliases'][0] == 'cex') {
    $command['callback'] = 'nimbus_drush_export';
  }
}

/**
 * A override function for ids.
 */
function nimbus_override_ids() {
  $output = new ConsoleOutput();
  $input = new ArrayInput([], new InputDefinition([]));
  $input->setInteractive((drush_get_option('yes') != NULL) ? FALSE : TRUE);
  $service = \Drupal::getContainer()->get('nimbus.nimbus_uuid');
  $service->uuidUpdateCommand($input, $output);
}

/**
 * The override method for the default drush export.
 *
 * @param string $destination
 *   Optional destination folder.
 *
 * @return array|bool|mixed
 *   The drush respone.
 */
function nimbus_drush_export($destination = NULL) {
  $output = new ConsoleOutput();
  $input = new ArrayInput([], new InputDefinition([]));
  $input->setInteractive((drush_get_option('yes') != NULL) ? FALSE : TRUE);
  $service = \Drupal::getContainer()->get('nimbus.nimbus_export');
  return $service->configurationExport($input, $output);
}

/**
 * The overrie method for the default drush import.
 *
 * @param string $source
 *   Optional destination folder.
 *
 * @return array|bool|mixed
 *   The drush respone.
 */
function nimbus_drush_import($source = NULL) {
  $output = new ConsoleOutput();
  $input = new ArrayInput([], new InputDefinition([]));
  $input->setInteractive((drush_get_option('yes') != NULL) ? FALSE : TRUE);
  $service = \Drupal::getContainer()->get('nimbus.nimbus_import');
  return $service->configurationImport($input, $output);
}
