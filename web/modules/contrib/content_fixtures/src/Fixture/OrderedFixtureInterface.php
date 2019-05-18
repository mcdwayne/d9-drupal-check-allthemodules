<?php

namespace Drupal\content_fixtures\Fixture;

/**
 * Interface OrderedFixtureInterface
 */
interface OrderedFixtureInterface {
  /**
   * Get the order of this fixture
   *
   * @return integer
   */
  public function getOrder();
}
