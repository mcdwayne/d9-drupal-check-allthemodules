<?php

namespace Drupal\Tests\entity_reference_integrity\Unit;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\entity_reference_integrity\DependencyFieldMapGenerator;
use Drupal\Tests\UnitTestCase;

/**
 * Test the field map generator.
 *
 * @group entity_reference_integrity
 */
class DependencyFieldMapGeneratorTest extends UnitTestCase {

  /**
   * Test the field map.
   *
   * @dataProvider fieldMapTestCases
   */
  public function testFieldMap($has_custom_storage, $has_storage_definition, $revision_metadata_fields, $field_map, $expected_result) {

    $target_entity_type_id = 'target_entity_type_id';

    $entityFieldManager = $this->getMock(EntityFieldManagerInterface::class);
    $entityFieldManager
      ->expects($this->any())
      ->method('getFieldMapByFieldType')
      ->willReturn($field_map);

    $storage_definition = $this->getMock(FieldStorageDefinitionInterface::class);
    $storage_definition
      ->expects($this->any())
      ->method('hasCustomStorage')
      ->willReturn($has_custom_storage);
    $storage_definition
      ->expects($this->any())
      ->method('getSetting')
      ->willReturn($target_entity_type_id);

    $entityFieldManager
      ->expects($this->any())
      ->method('getFieldStorageDefinitions')
      ->willReturn($has_storage_definition ? [
        'field_name' => $storage_definition,
      ] : []);

    $entityTypeManager = $this->getMock(EntityTypeManagerInterface::class);
    $mockDefinition = $this->getMock(ContentEntityTypeInterface::class);
    $mockDefinition->method('getRevisionMetadataKeys')->willReturn($revision_metadata_fields);
    $entityTypeManager
      ->method('getDefinition')
      ->willReturn($mockDefinition);
    $field_map = new DependencyFieldMapGenerator($entityFieldManager, $entityTypeManager, 'entity_reference', 'target_type');

    $generated_map = $field_map->getReferencingFields($target_entity_type_id);
    $this->assertEquals($expected_result, $generated_map);
  }

  /**
   * Test cases for ::testFieldMap.
   */
  public function fieldMapTestCases() {
    return [
      'Standard field' => [
        FALSE,
        TRUE,
        [],
        [
          'foo_entity_type_id' => [
            'field_name' => [],
          ],
        ],
        [
          'foo_entity_type_id' => [
            'field_name',
          ],
        ],
      ],
      'Custom storage field' => [
        TRUE,
        TRUE,
        [],
        [
          'foo_entity_type_id' => [
            'field_name' => [],
          ],
        ],
        [],
      ],
      'No storage definition' => [
        FALSE,
        FALSE,
        [],
        [
          'foo_entity_type_id' => [
            'field_name' => [],
          ],
        ],
        [],
      ],
      'Field is revision metadata' => [
        FALSE,
        TRUE,
        ['field_name'],
        [
          'foo_entity_type_id' => [
            'field_name' => [],
          ],
        ],
        [],
      ],
    ];
  }

}
