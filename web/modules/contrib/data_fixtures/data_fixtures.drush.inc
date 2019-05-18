<?php

/**
 * @file
 * Contains our drush command to load and unload fixtures.
 *
 * @command fixtures-load
 *  Load all enabled fixtures that are currently installed
 *
 * @command fixtures-unload
 *  Unload all enabled fixtures that are currently installed.
 *  Every fixture should be able to revert the content it added
 *
 * @command fixtures-reload
 *  Unloads, and then loads all enabled fixtures currently installed
 */

use Drupal\data_fixtures\FixturesGenerator;

/**
 * Implements hook_drush_command().
 */
function data_fixtures_drush_command() {
  $items = [];

  $items['fixtures-list'] = [
    'description' => 'List all enabled fixtures that are currently installed',
    'drupal dependencies' => ['data_fixtures'],
    'aliases' => ['fixtures:list'],
  ];

  $items['fixtures-load'] = [
    'description' => 'Load all enabled fixtures that are currently installed',
    'drupal dependencies' => ['data_fixtures'],
    'aliases' => ['fixtures:load'],
    'arguments' => [
      'fixture_alias' => 'The alias of the fixture to load. "all" if you want to load all fixtures',
    ],
  ];

  $items['fixtures-unload'] = [
    'description' => 'Unload all enabled fixtures that are currently installed.',
    'drupal dependencies' => ['data_fixtures'],
    'aliases' => ['fixtures:unload'],
    'arguments' => [
      'fixture_alias' => 'The alias of the fixture to unload. "all" if you want to unload all fixtures',
    ],
  ];

  $items['fixtures-reload'] = [
    'description' => 'Reload (unload + load) all enabled fixtures that are currently installed',
    'drupal dependencies' => ['data_fixtures'],
    'aliases' => ['fixtures:reload'],
    'arguments' => [
      'fixture_alias' => 'The alias of the fixture to reload. "all" if you want to reload all fixtures',
    ],
  ];

  return $items;
}

/**
 * Call back function for fixtures-load.
 */
function drush_data_fixtures_fixtures_list() {
  drush_print('Listing all fixtures:');
  foreach (_data_fixtures_get_fixtures('all') as $generator) {
    drush_print(' - ' . $generator->prettyPrint());
  }
}

/**
 * Call back function for fixtures-load.
 *
 * @param string $fixture_alias
 *   Fixture to load, defaults to all.
 */
function drush_data_fixtures_fixtures_load($fixture_alias = 'all') {
  foreach (_data_fixtures_get_fixtures($fixture_alias) as $generator) {
    drush_print('Loading: ' . $generator->prettyPrint());
    $generator->getGenerator()->load();
  }
}

/**
 * Call back function for fixtures-load.
 *
 * @param string $fixture_alias
 *   Fixture to unload, defaults to all.
 */
function drush_data_fixtures_fixtures_unload($fixture_alias = 'all') {
  foreach (_data_fixtures_get_fixtures($fixture_alias, TRUE) as $generator) {
    drush_print('Unloading: ' . $generator->prettyPrint());
    $generator->getGenerator()->unLoad();
  }
}

/**
 * Call back function for fixtures-load.
 *
 * @param string $fixture_alias
 *   Fixture to reload, defaults to all.
 */
function drush_data_fixtures_fixtures_reload($fixture_alias = 'all') {
  drush_data_fixtures_fixtures_unload($fixture_alias);
  drush_data_fixtures_fixtures_load($fixture_alias);
}

/**
 * Returns the fixtures matching to the given alis.
 *
 * @param string $fixtures_alias
 *   Alias of the fixture (all if you want to get all enabled fixtures)
 * @param bool $reverse
 *   Retrieve the generators in reverse order if set to true.
 *
 * @return \Drupal\data_fixtures\FixturesGenerator[]
 *   Array of fixtures
 */
function _data_fixtures_get_fixtures($fixtures_alias, $reverse = FALSE) {
  /** @var \Drupal\data_fixtures\FixturesManager $fixturesManager */
  $fixturesManager = Drupal::service('data_fixtures');

  if ('all' === $fixtures_alias) {
    return $fixturesManager->getGenerators($reverse);
  }

  return array_filter(
    $fixturesManager->getGenerators(),
    function (FixturesGenerator $fixtures_generator) use ($fixtures_alias) {
      return $fixtures_generator->getAlias() === $fixtures_alias;
    }
  );
}
