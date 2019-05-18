<?php

namespace Drupal\Tests\entity_pilot_map_config\Kernel;

use Drupal\entity_pilot_map_config\Entity\BundleMapping;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests BundleMapping config entity.
 *
 * @group entity_pilot
 *
 * @coversDefaultClass \Drupal\entity_pilot_map_config\Entity\BundleMapping
 */
class BundleMappingTest extends KernelTestBase {

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
    /** @var \Drupal\entity_pilot_map_config\BundleMappingInterface $mapping */
    $mapping = BundleMapping::create([
      'id' => 'test_mapping',
      'label' => 'Test mapping',
    ]);
    $bundle_mapping = [
      [
        'entity_type' => 'node',
        'source_bundle_name' => 'article',
        'destination_bundle_name' => 'post',
      ],
    ];
    $mapping->setMappings($bundle_mapping);
    $mapping->save();
    \Drupal::entityTypeManager()->getStorage('ep_bundle_mapping')->resetCache(['test_mapping']);
    /** @var \Drupal\entity_pilot_map_config\BundleMappingInterface $mapping */
    $mapping = BundleMapping::load('test_mapping');
    $this->assertEquals($bundle_mapping, $mapping->getMappings());
    $additional_mapping = [
      'entity_type' => 'node',
      'source_bundle_name' => 'basic_page',
      'destination_bundle_name' => 'page',
    ];
    $mapping->addMapping($additional_mapping);
    $mapping->save();
    \Drupal::entityTypeManager()->getStorage('ep_bundle_mapping')->resetCache(['test_mapping']);
    /** @var \Drupal\entity_pilot_map_config\BundleMappingInterface $mapping */
    $mapping = BundleMapping::load('test_mapping');
    $bundle_mapping[] = $additional_mapping;
    $this->assertEquals($bundle_mapping, $mapping->getMappings());
  }

  /**
   * Tests calculateDependencies().
   *
   * @covers ::calculateDependencies
   */
  public function testCalculateDependencies() {
    /** @var \Drupal\entity_pilot_map_config\BundleMappingInterface $mapping */
    $mapping = BundleMapping::create([
      'id' => 'test_mapping',
      'label' => 'Test mapping',
    ]);
    $field_mapping = [
      [
        'entity_type' => 'node',
        'source_bundle_name' => 'article',
        'destination_bundle_name' => 'post',
      ],
    ];
    $mapping->setMappings($field_mapping);
    $mapping->save();
    $dependencies = $mapping->calculateDependencies()->getDependencies();
    $this->assertEquals([
      'config' => ['node.type.post'],
    ], $dependencies);
  }

}
