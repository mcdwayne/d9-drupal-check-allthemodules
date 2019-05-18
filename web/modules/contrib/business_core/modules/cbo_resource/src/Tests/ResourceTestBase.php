<?php

namespace Drupal\cbo_resource\Tests;

use Drupal\people\Tests\PeopleTestBase;
use Drupal\cbo_resource\Entity\CboResource;

/**
 * Provides helper functions for resource module tests.
 */
abstract class ResourceTestBase extends PeopleTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['cbo_resource'];

  /**
   * A resource.
   *
   * @var \Drupal\cbo_resource\ResourceInterface
   */
  protected $resource;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->resource = $this->createResource('employee');
  }

  /**
   * Creates a resource based on default settings.
   */
  protected function createResource($type, array $settings = []) {
    // Populate defaults array.
    $settings += [
      'type' => $type,
      'title' => $this->randomMachineName(8),
      'number' => $this->randomMachineName(8),
    ];
    $entity = CboResource::create($settings);
    $entity->save();

    return $entity;
  }

}
