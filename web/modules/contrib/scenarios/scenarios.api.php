<?php

use Drupal\migrate\Plugin\MigrationInterface;

/**
 * @file
 * Hooks specific to the Scenarios module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Defines a set of migrations to be used for importing scenario content.
 *
 * @return array
 *   An associative array of scenarios, keyed by the machine name of the
 *   scenario, containing the following key-value pairs:
 *   - label: (required) The human-readable name of the scenario.
 *   - description: A short description for the scenario.
 *   - migrations: An optional array of migrations associated with the scenario.
 *   - imports: An optional array of default content deploy content directories.
 *   - cdf: An optional array listing directories containing cdf to be imported.
 *     Note: cdf is only supported by enabling the scenarios_contenthub module.
 *
 * @see hook_scenarios_info_alter()
 */
function hook_scenarios_info() {
  return [
    'myscenario' => [
      'label' => t('My Scenario'),
      'description' => t('An example scenario.'),
      'migrations' => [
        'myscenario_node_articles',
        'myscenario_block_slideshow',
      ],
      'cdf' => [
        '/cdf/nodes',
        '/cdf/users',
      ],
    ],
    'myotherscenario' => [
      'label' => t('My Other Scenario'),
      'description' => t('Another example scenario.'),
      'imports' => ['/content'],
    ],
  ];
}

/**
 * Alters the list of scenario migrations.
 *
 * @param array $info
 *   An associative array of migration IDs keyed by the machine name of the
 *   scenario module that provides them.
 *
 * @see hook_scenarios_info()
 */
function hook_scenarios_info_alter(&$info) {
  // Remove the existing block slideshow migration and replace it with a node
  // slideshow migration.
  unset($info['myscenario']['migrations']['myscenario_block_slideshow']);

  $info['myscenario']['migrations'][] = 'myscenario_node_slideshow';
}

/**
 * Allows modules to act on completion of scenario migrations.
 *
 * @param MigrationInterface $migration
 *   A scenario migration.
 */
function hook_scenarios_migration_finished(MigrationInterface $migration) {
  // Display a message to notify the user that the migration has finished.
  if ($migration->id() == 'myscenario_node_slideshow') {
    drupal_set_message(t('Finished importing the slideshow slides.'));
  }
}

/**
 * Allows modules to act after a scenario has been enabled.
 *
 * @param string $scenario
 *   The machine name of the scenario.
 */
function hook_scenarios_post_enable($scenario) {
  // Show a custom message after enabling a scenario.
  if ($info = scenarios_info($scenario)) {
    drupal_set_message(t('@scenario has been enabled!', ['@scenario' => $info['label']]));
  }
}

/**
 * Allows modules to act after a scenario has been uninstalled.
 *
 * @param string $scenario
 *   The machine name of the scenario.
 */
function hook_scenarios_post_uninstall($scenario) {
  // Show a custom message after uninstalling a scenario.
  if ($info = scenarios_info($scenario)) {
    drupal_set_message(t('@scenario has been uninstalled!', ['@scenario' => $info['label']]));
  }
}

/**
 * Allows modules to act after a scenario has been reset.
 *
 * @param string $scenario
 *   The machine name of the scenario.
 */
function hook_scenarios_post_reset($scenario) {
  // Show a custom message after resetting a scenario.
  if ($info = scenarios_info($scenario)) {
    drupal_set_message(t('@scenario has been reset!', ['@scenario' => $info['label']]));
  }
}

/**
 * @} End of "addtogroup hooks".
 */
