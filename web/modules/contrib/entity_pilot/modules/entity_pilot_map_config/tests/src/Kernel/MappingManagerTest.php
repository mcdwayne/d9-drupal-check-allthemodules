<?php

namespace Drupal\Tests\entity_pilot_map_config\Kernel;

use Drupal\entity_pilot\Data\FlightManifest;
use Drupal\entity_pilot_map_config\BundleMappingInterface;
use Drupal\entity_pilot_map_config\ConfigurationDifference;
use Drupal\entity_pilot_map_config\FieldMappingInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests Mapping manager service.
 *
 * @group entity_pilot
 *
 * @coversDefaultClass Drupal\entity_pilot_map_config\MappingManager
 */
class MappingManagerTest extends KernelTestBase {

  use TestMappingTrait;

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
    $this->installEntitySchema('ep_arrival');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installConfig('node');
    $this->installConfig('entity_pilot_map_config_test');
    $this->createTestMappings();

  }

  /**
   * Tests loadForConfigurationDifference().
   *
   * @covers ::loadForConfigurationDifference
   */
  public function testLoadForConfigurationDifference() {
    $difference = new ConfigurationDifference([
      'node' => [
        'field_image' => 'image',
        'field_foo' => 'text',
      ],
    ], [
      'node' => [
        'foo', 'article',
      ],
    ]);
    /** @var \Drupal\entity_pilot_map_config\MappingManagerInterface $manager */
    $manager = \Drupal::service('entity_pilot_map_config.mapping_manager');
    /** @var \Drupal\entity_pilot_map_config\MatchingMappingsResult $results */
    $results = $manager->loadForConfigurationDifference($difference);
    $field_mappings = $results->getFieldMappings();
    $this->assertCount(1, $field_mappings);
    $bundle_mappings = $results->getBundleMappings();
    $this->assertCount(1, $bundle_mappings);
    /** @var \Drupal\entity_pilot_map_config\FieldMappingInterface $field_mapping */
    $field_mapping = reset($field_mappings);
    /** @var \Drupal\entity_pilot_map_config\BundleMappingInterface $bundle_mapping */
    $bundle_mapping = reset($bundle_mappings);
    $this->assertEquals('test_mapping', $field_mapping->id());
    $this->assertEquals('test_mapping', $bundle_mapping->id());
  }

  /**
   * Tests create bundle mapping creation.
   *
   * @covers ::createBundleMappingFromConfigurationDifference
   */
  public function testCreateBundleMappingFromConfigurationDifference() {
    $difference = new ConfigurationDifference([
      'node' => [
        'field_image' => 'image',
        'field_foo' => 'text',
      ],
    ], [
      'node' => [
        'foo', 'article',
      ],
    ]);
    $flight_manifest = (new FlightManifest())
      ->setSite('http://example.com')
      ->setRemoteId(12)
      ->setCarrierId('foobar');

    /** @var \Drupal\entity_pilot_map_config\MappingManagerInterface $manager */
    $manager = \Drupal::service('entity_pilot_map_config.mapping_manager');
    $bundle_mapping = $manager->createBundleMappingFromConfigurationDifference($difference, $flight_manifest);
    $this->assertEquals([
      [
        'entity_type' => 'node',
        'source_bundle_name' => 'foo',
        'destination_bundle_name' => BundleMappingInterface::IGNORE_BUNDLE,
      ],
      [
        'entity_type' => 'node',
        'source_bundle_name' => 'article',
        'destination_bundle_name' => BundleMappingInterface::IGNORE_BUNDLE,
      ],
    ], $bundle_mapping->getMappings());
    $this->assertEquals('http://example.com', $bundle_mapping->label());
  }

  /**
   * Tests create field mapping creation.
   *
   * @covers ::createFieldMappingFromConfigurationDifference
   */
  public function testCreateFieldMappingFromConfigurationDifference() {
    $difference = new ConfigurationDifference([
      'node' => [
        'field_image' => 'image',
        'field_foo' => 'text',
      ],
    ], [
      'node' => [
        'foo',
        'article',
      ],
    ]);
    $flight_manifest = (new FlightManifest())
      ->setSite('http://example.com')
      ->setRemoteId(12)
      ->setCarrierId('foobar');

    /** @var \Drupal\entity_pilot_map_config\MappingManagerInterface $manager */
    $manager = \Drupal::service('entity_pilot_map_config.mapping_manager');
    $field_mapping = $manager->createFieldMappingFromConfigurationDifference($difference, $flight_manifest);
    $this->assertEquals([
      [
        'entity_type' => 'node',
        'source_field_name' => 'field_image',
        'destination_field_name' => FieldMappingInterface::IGNORE_FIELD,
        'field_type' => 'image',
      ],
      [
        'entity_type' => 'node',
        'source_field_name' => 'field_foo',
        'destination_field_name' => FieldMappingInterface::IGNORE_FIELD,
        'field_type' => 'text',
      ],
    ], $field_mapping->getMappings());
    $this->assertEquals('http://example.com', $field_mapping->label());
  }

}
