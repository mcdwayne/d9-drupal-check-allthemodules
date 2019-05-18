<?php

namespace Drupal\cbo_location\Tests;

use Drupal\cbo_location\Entity\Location;
use Drupal\simpletest\WebTestBase;

/**
 * Provides helper functions.
 */
abstract class LocationTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['cbo_location'];

  /**
   * A location.
   *
   * @var \Drupal\cbo_location\LocationInterface
   */
  protected $location;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->location = $this->createLocation();
  }

  /**
   * Creates a location based on default settings.
   */
  protected function createLocation(array $settings = []) {
    // Populate defaults array.
    $settings += [
      'name' => $this->randomMachineName(8),
      'description' => $this->randomMachineName(8),
    ];
    $entity = Location::create($settings);
    $entity->save();

    return $entity;
  }

}
