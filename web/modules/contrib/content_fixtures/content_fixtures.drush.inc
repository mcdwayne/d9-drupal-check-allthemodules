<?php

/**
 * Implements hook_drush_command().
 */
function content_fixtures_drush_command() {
  $items = array();

  $items['content-fixtures-list'] = [
    'description' => 'List all available fixtures.',
    'drupal dependencies' => ['content_fixtures'],
    'aliases' => ['content-fixtures:list'],
    'options' => array(
      'groups' => array(
        'value' => TRUE,
      ),
    ),
    'examples' => array(
      'drush content-fixtures:list' => 'Lists all fixtures.',
      'drush content-fixtures:list --groups=group1,group2,group3' => 'Lists fixtures belonging to three groups.',
    ),
  ];

  $items['content-fixtures-load'] = [
    'description' => 'Load all fixtures.',
    'drupal dependencies' => ['content_fixtures'],
    'aliases' => ['content-fixtures:load'],
    'options' => array(
      'groups' => array(
        'value' => TRUE,
      ),
    ),
    'examples' => array(
      'drush content-fixtures:load' => 'Loads all fixtures.',
      'drush content-fixtures:load --groups=group1,group2,group3' => 'Loads fixtures belonging to three groups.',
    ),
  ];

  return $items;
}

/**
 * Call back function for fixtures-load
 */
function drush_content_fixtures_list() {
  $groups = [];
  $groupsOption = drush_get_option('groups');
  if ($groupsOption) {
    $groups = explode(',', $groupsOption);
    drush_print('Selected groups: ' . implode(', ', $groups));
  }

  $fixtures = Drupal::service('content_fixtures_loader')->getFixtures($groups);

  if (empty($fixtures)) {
    drush_print('No fixtures found.');
    return;
  }

  drush_print('Fixtures in order of execution:');
  $index = 1;
  foreach ($fixtures as $fixture) {
    drush_print("$index: " . \get_class($fixture));
    $index++;
  }
}

/**
 * Call back function for fixtures-load
 *
 * @var string $fixture_alias
 */
function drush_content_fixtures_load() {
  $groups = [];
  $groupsOption = drush_get_option('groups');
  if ($groupsOption) {
    $groups = explode(',', $groupsOption);
    drush_print('Selected groups: ' . implode(', ', $groups));
  }

  if (!drush_confirm('Are you sure you want to delete all existing content and load fixtures?')) {
    return;
  }

  drush_print('Deleting existing content.');
  Drupal::service('content_fixtures_default_purger')->purge();

  $fixtures = Drupal::service('content_fixtures_loader')->getFixtures($groups);

  if (empty($fixtures)) {
    drush_print('No fixtures found.');
    return;
  }

  foreach ($fixtures as $fixture) {
    drush_print('Loading fixture: ' . \get_class($fixture));
    $fixture->load();
  }
  drush_print('Done.');
}
