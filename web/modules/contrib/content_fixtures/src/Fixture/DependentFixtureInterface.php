<?php

namespace Drupal\content_fixtures\Fixture;

/**
 * Interface DependentFixtureInterface
 */
interface DependentFixtureInterface {
  /**
   * Get the dependencies of this fixture.
   *
   * @return string[]
   */
  public function getDependencies();
}
