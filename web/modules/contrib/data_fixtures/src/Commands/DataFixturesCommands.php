<?php

namespace Drupal\data_fixtures\Commands;

use Drupal\data_fixtures\FixturesGenerator;
use Drupal\data_fixtures\FixturesManager;
use Drush\Commands\DrushCommands;

/**
 * Drush ^9 commands for data_fixtures module.
 */
class DataFixturesCommands extends DrushCommands {

  /**
   * The fixture manager service.
   *
   * @var \Drupal\data_fixtures\FixturesManager
   */
  protected $fixturesManager;

  /**
   * DataFixturesCommands constructor.
   *
   * @param \Drupal\data_fixtures\FixturesManager $fixturesManager
   *   Fixture manager service.
   */
  public function __construct(FixturesManager $fixturesManager) {
    parent::__construct();
    $this->fixturesManager = $fixturesManager;
  }

  /**
   * List all enabled fixtures that are currently installed.
   *
   * @command fixtures:list
   * @aliases fixtures-list
   *
   * @validate-module-enabled data_fixtures
   */
  public function listFixtures() {
    $this->output()->writeln('Listing all fixtures:');
    foreach ($this->getFixtures('all') as $generator) {
      $this->output()->writeln(' - ' . $generator->prettyPrint());
    }
  }

  /**
   * Load all enabled fixtures that are currently installed.
   *
   * @param string $fixture_alias
   *   The alias of the fixture to load. "all" if you want to load all fixtures.
   *
   * @command fixtures:load
   * @aliases fixtures-load
   *
   * @validate-module-enabled data_fixtures
   */
  public function load($fixture_alias) {
    foreach ($this->getFixtures($fixture_alias) as $generator) {
      $this->output()->writeln('Loading: ' . $generator->prettyPrint());
      $generator->getGenerator()->load();
    }
  }

  /**
   * Unload all enabled fixtures that are currently installed.
   *
   * @param string $fixture_alias
   *   The alias of the fixture to unload.
   *   "all" if you want to unload all fixtures.
   *
   * @command fixtures:unload
   * @aliases fixtures-unload
   *
   * @validate-module-enabled data_fixtures
   */
  public function unload($fixture_alias) {
    foreach ($this->getFixtures($fixture_alias, TRUE) as $generator) {
      $this->output()->writeln('Unloading: ' . $generator->prettyPrint());
      $generator->getGenerator()->unLoad();
    }
  }

  /**
   * Reload (unload + load) all enabled fixtures that are currently installed.
   *
   * @param string $fixture_alias
   *   The alias of the fixture to reload.
   *   "all" if you want to reload all fixtures.
   *
   * @command fixtures:reload
   * @aliases fixtures-reload
   *
   * @validate-module-enabled data_fixtures
   */
  public function reload($fixture_alias) {
    $this->unload($fixture_alias);
    $this->load($fixture_alias);
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
  private function getFixtures($fixtures_alias, $reverse = FALSE) {
    if ('all' === $fixtures_alias) {
      return $this->fixturesManager->getGenerators($reverse);
    }

    return array_filter(
      $this->fixturesManager->getGenerators(),
      function (FixturesGenerator $fixtures_generator) use ($fixtures_alias) {
        return $fixtures_generator->getAlias() === $fixtures_alias;
      }
    );
  }

}
