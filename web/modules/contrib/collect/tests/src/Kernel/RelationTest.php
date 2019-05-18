<?php

namespace Drupal\Tests\collect\Kernel;

use Drupal\collect\Entity\Container;
use Drupal\collect\Entity\Relation;
use Drupal\collect\Entity\RelationType;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the relations feature.
 *
 * @group collect
 */
class RelationTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'collect',
    'hal',
    'rest',
    'serialization',
    'collect_test',
    'collect_common',
  ];

  /**
   * Tests the dependency calculation of relation types.
   */
  public function testDependencies() {
    $relation_type = RelationType::create([
      'id' => 'foo',
      'uri_pattern' => 'foo',
      'plugin_id' => 'test_hugs',
    ]);
    $dependencies = $relation_type->calculateDependencies();
    $this->assertEqual(['module' => ['collect_test']], $dependencies);
  }

  /**
   * Tests validation of relation entities.
   */
  public function testValidation() {
    $this->installEntitySchema('collect_relation');
    $this->installEntitySchema('collect_container');

    // Empty values.
    $relation = Relation::create();
    $violations = $relation->validate();
    $this->assertEqual(3, count($violations));
    $this->assertEqual($violations[0]->getPropertyPath(), 'source_uri');
    $this->assertEqual($violations[1]->getPropertyPath(), 'target_uri');
    $this->assertEqual($violations[2]->getPropertyPath(), 'relation_uri');

    // Invalid container IDs.
    $relation = Relation::create([
      'source_uri' => 'thing',
      'source_id' => 747,
      'target_uri' => 'thang',
      'target_id' => 9001,
      'relation_uri' => 'foo',
    ]);
    $violations = $relation->validate();
    $this->assertEqual(2, count($violations));
    $this->assertEqual($violations[0]->getPropertyPath(), 'source_id.0.target_id');
    $this->assertEqual($violations[1]->getPropertyPath(), 'target_id.0.target_id');

    // Non-matching container URIs.
    $container = Container::create([
      'origin_uri' => 'apple',
      'schema_uri' => '',
      'type' => '',
      'data' => '',
    ]);
    $container->save();
    $relation = Relation::create([
      'source_id' => $container->id(),
      'source_uri' => 'orange',
      'target_id' => $container->id(),
      'target_uri' => 'banana',
      'relation_uri' => 'foo',
    ]);
    $violations = $relation->validate();
    $this->assertEqual(2, count($violations));
    $this->assertEqual($violations[0]->getPropertyPath(), 'source_uri.0');
    $this->assertEqual($violations[1]->getPropertyPath(), 'target_uri.0');
  }
}
