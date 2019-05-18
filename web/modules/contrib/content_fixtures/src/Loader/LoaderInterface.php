<?php

namespace Drupal\content_fixtures\Loader;

use Drupal\content_fixtures\Fixture\FixtureInterface;

/**
 * Interface LoaderInterface
 */
interface LoaderInterface {

  /**
   * Load all fixtures.
   */
  public function loadFixtures();

  /**
   * @param FixtureInterface $fixture
   *
   * @return void
   */
  public function addFixture(FixtureInterface $fixture);

  /**
   * @param array $groups
   *
   * @return FixtureInterface[]
   */
  public function getFixtures(array $groups = []);

}
