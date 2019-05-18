<?php

/**
 * @file
 * Drush integration for Node Package Manager module.
 */

/**
 * Implements hook_drush_command().
 */
function npm_drush_command() {
  $items['npm-list'] = [
    'description' => 'Show a list of available node package managers.',
  ];
  return $items;
}

/**
 * Callback function for npm-list command.
 */
function drush_npm_list() {
  $plugin_manager = Drupal::service('plugin.manager.npm_executable');

  $rows[] = [
    'ID',
    'Label',
    'Description',
    'Weight',
  ];

  foreach ($plugin_manager->getDefinitions() as $definition) {
    $npm = $plugin_manager->createInstance($definition['id']);
    $rows[] = [
      $definition['id'],
      $definition['label'],
      $definition['description'],
      $definition['weight'],
    ];
  }

  drush_print_table($rows);
}
