<?php

namespace Drupal\Tests\entity_pilot_map_config\Kernel;

use Drupal\entity_pilot_map_config\Entity\BundleMapping;
use Drupal\entity_pilot_map_config\Entity\FieldMapping;
use Drupal\entity_pilot_map_config\FieldMappingInterface;

/**
 * Defines a trait for creating test mappings.
 */
trait TestMappingTrait {

  /**
   * Creates some test field and bundle mappings.
   */
  protected function createTestMappings() {
    /** @var \Drupal\entity_pilot_map_config\BundleMappingInterface $mapping */
    // One that handles article => post and foo => bar.
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
      [
        'entity_type' => 'node',
        'source_bundle_name' => 'foo',
        'destination_bundle_name' => 'bar',
      ],
    ];
    $mapping->setMappings($bundle_mapping);
    $mapping->save();
    // One that does webform => contact.
    $mapping = BundleMapping::create([
      'id' => 'another_mapping',
      'label' => 'Test mapping',
    ]);
    $bundle_mapping = [
      [
        'entity_type' => 'node',
        'source_bundle_name' => 'webform',
        'destination_bundle_name' => 'contact',
      ],
    ];
    $mapping->setMappings($bundle_mapping);
    $mapping->save();
    /** @var \Drupal\entity_pilot_map_config\FieldMappingInterface $mapping */
    // One that handles field_image to field_images and field_foo to field_bar.
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
      [
        'entity_type' => 'node',
        'source_field_name' => 'path',
        'destination_field_name' => FieldMappingInterface::IGNORE_FIELD,
        'field_type' => 'path',
      ],
      [
        'entity_type' => 'node',
        'source_field_name' => 'comment',
        'destination_field_name' => FieldMappingInterface::IGNORE_FIELD,
        'field_type' => 'comment',
      ],
      [
        'entity_type' => 'node',
        'source_field_name' => 'field_tags',
        'destination_field_name' => FieldMappingInterface::IGNORE_FIELD,
        'field_type' => 'entity_reference',
      ],
      [
        'entity_type' => 'user',
        'source_field_name' => 'user_picture',
        'destination_field_name' => FieldMappingInterface::IGNORE_FIELD,
        'field_type' => 'image',
      ],
      [
        'entity_type' => 'node',
        'source_field_name' => 'field_foo',
        'destination_field_name' => 'field_bar',
        'field_type' => 'text',
      ],
    ];
    $mapping->setMappings($field_mapping);
    $mapping->save();
    // One that does some other random field.
    $mapping = FieldMapping::create([
      'id' => 'another_mapping',
      'label' => 'Test mapping',
    ]);
    $field_mapping = [
      [
        'entity_type' => 'node',
        'source_field_name' => 'field_hoohar',
        'destination_field_name' => 'field_wizwang',
        'field_type' => 'text',
      ],
    ];
    $mapping->setMappings($field_mapping);
    $mapping->save();
  }

}
