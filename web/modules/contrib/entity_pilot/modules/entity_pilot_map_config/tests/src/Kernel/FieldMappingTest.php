<?php

namespace Drupal\Tests\entity_pilot_map_config\Kernel;

use Drupal\entity_pilot_map_config\Entity\FieldMapping;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests FieldMapping config entity.
 *
 * @group entity_pilot
 *
 * @coversDefaultClass \Drupal\entity_pilot_map_config\Entity\FieldMapping
 */
class FieldMappingTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_pilot_map_config',
    'entity_pilot_map_config_test',
    'entity_pilot',
    'serialization',
    'hal',
    'rest',
    'text',
    'node',
    'user',
    'system',
    'field',
    'file',
    'image',
    'filter',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig('entity_pilot_map_config');
  }

  /**
   * Tests setMappings(), addMapping() and getMappings().
   *
   * @covers ::setMappings
   * @covers ::getMappings
   * @covers ::addMapping
   */
  public function testGetSetAddMappings() {
    /** @var \Drupal\entity_pilot_map_config\FieldMappingInterface $mapping */
    $mapping = FieldMapping::create([
      'id' => 'test_mapping',
      'label' => 'Test mapping',
    ]);
    $field_mapping = [
      [
        'entity_type' => 'node',
        'source_field_name' => 'field_image',
        'destination_field_name' => 'field_images',
        'field_type' => 'image',
      ],
    ];
    $mapping->setMappings($field_mapping);
    $mapping->save();
    \Drupal::entityTypeManager()->getStorage('ep_field_mapping')->resetCache(['test_mapping']);
    /** @var \Drupal\entity_pilot_map_config\FieldMappingInterface $mapping */
    $mapping = FieldMapping::load('test_mapping');
    $this->assertEquals($field_mapping, $mapping->getMappings());
    $additional_mapping = [
      'entity_type' => 'node',
      'source_field_name' => 'field_tag',
      'destination_field_name' => 'field_tags',
      'field_type' => 'entity_reference',
    ];
    $mapping->addMapping($additional_mapping);
    $mapping->save();
    \Drupal::entityTypeManager()->getStorage('ep_field_mapping')->resetCache(['test_mapping']);
    /** @var \Drupal\entity_pilot_map_config\FieldMappingInterface $mapping */
    $mapping = FieldMapping::load('test_mapping');
    $field_mapping[] = $additional_mapping;
    $this->assertEquals($field_mapping, $mapping->getMappings());
  }

  /**
   * Tests calculateDependencies().
   *
   * @covers ::calculateDependencies
   */
  public function testCalculateDependencies() {
    /** @var \Drupal\entity_pilot_map_config\FieldMappingInterface $mapping */
    $mapping = FieldMapping::create([
      'id' => 'test_mapping',
      'label' => 'Test mapping',
    ]);
    $field_mapping = [
      [
        'entity_type' => 'node',
        'source_field_name' => 'field_image',
        'destination_field_name' => 'field_images',
        'field_type' => 'image',
      ],
    ];
    $mapping->setMappings($field_mapping);
    $mapping->save();
    $dependencies = $mapping->calculateDependencies()->getDependencies();
    $this->assertEquals([
      'config' => ['field.storage.node.field_images'],
    ], $dependencies);
  }

}
