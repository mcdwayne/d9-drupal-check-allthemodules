<?php

namespace Drupal\content_fixtures\Fixture;

/**
 * Interface FixtureGroupInterface
 */
interface FixtureGroupInterface extends FixtureInterface {
  /**
   * Groups a fixture belongs to.
   *
   * @return string[]
   */
  public function getGroups();
}
