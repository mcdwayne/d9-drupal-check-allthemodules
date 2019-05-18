<?php

namespace Drupal\Tests\blizz_bulk_creator\Unit;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Provides consistent test data for implemented unit tests.
 *
 * @group blizz_bulk_creator
 */
abstract class UnitTestBase extends UnitTestCase {

  /**
   * Testdata structure.
   *
   * Holds the information architecture of the mock objects as
   * well as comparison data for the single unit tests.
   *
   * @var array
   */
  protected $testdata;

  /**
   * The entity type manager mock object.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info service mock object.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityBundleInfoService;

  /**
   * The entity field manager mock object.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity type repository interface mock object.
   *
   * @var \Drupal\Core\Entity\EntityTypeRepositoryInterface
   */
  protected $entityTypeRepository;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Mock Drupal core services.
    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->entityBundleInfoService = $this->prophesize(EntityTypeBundleInfoInterface::class);
    $this->entityFieldManager = $this->prophesize(EntityFieldManagerInterface::class);
    $this->entityTypeRepository = $this->prophesize(EntityTypeRepositoryInterface::class);

    // Prepare a realistic testing scenario.
    $this->prepareMockedObjectData();

    // Trigger implemented unit tests.
    $this->setUpTestObject();

  }

  /**
   * Prepares consistent teat data for all tests.
   *
   * @return array
   *   Some mock objects to test on.
   */
  private function prepareMockedObjectData() {

    /*
     * Prepare some common mock objects.
     */

    // Mocked storage definition for a multivalue field.
    $unlimitedvalueFieldStorageDefinition = $this->prophesize(FieldStorageDefinitionInterface::class);
    $unlimitedvalueFieldStorageDefinition->getCardinality()->willReturn(-1);
    $unlimitedvalueFieldStorageDefinition = $unlimitedvalueFieldStorageDefinition->reveal();

    // Mocked storage definition for a singlevalue field.
    $singlevalueFieldStorageDefinition = $this->prophesize(FieldStorageDefinitionInterface::class);
    $singlevalueFieldStorageDefinition->getCardinality()->willReturn(1);
    $singlevalueFieldStorageDefinition = $singlevalueFieldStorageDefinition->reveal();

    // Base field definition A.
    $base_field_a = $this->prophesize(BaseFieldDefinition::class);
    $base_field_a->getLabel()->willReturn('Base Field Definition A');
    $base_field_a->getName()->willReturn('base_field_a');
    $base_field_a->getType()->willReturn('integer');
    $base_field_a = $base_field_a->reveal();

    // Base field definition B.
    $base_field_b = $this->prophesize(BaseFieldDefinition::class);
    $base_field_b->getLabel()->willReturn('Base Field Definition B');
    $base_field_b->getName()->willReturn('base_field_b');
    $base_field_b->getType()->willReturn('string');
    $base_field_b = $base_field_b->reveal();

    /*
     * The test structure.
     */

    $this->testdata = [
      'entity_types' => [
        'node' => [
          'label' => 'Node',
          'fields' => [
            'field_node_text' => [
              'field_type' => 'string',
              'label' => 'Text',
              'handler' => NULL,
              'handler_settings' => [],
              'field_storage_definition' => $singlevalueFieldStorageDefinition,
            ],
            'field_node_field_entity_reference_media_image' => [
              'field_type' => 'entity_reference',
              'label' => 'Entity Reference: media:image',
              'handler' => 'default:media',
              'handler_settings' => ['target_bundles' => ['image' => 'image']],
              'field_storage_definition' => $singlevalueFieldStorageDefinition,
            ],
            'field_node_field_entity_reference_media_audio' => [
              'field_type' => 'entity_reference',
              'label' => 'Entity Reference: media:audio',
              'handler' => 'default:media',
              'handler_settings' => ['target_bundles' => ['audio' => 'audio']],
              'field_storage_definition' => $singlevalueFieldStorageDefinition,
            ],
            'field_node_field_entity_reference_revisions_paragraph_text' => [
              'field_type' => 'entity_reference_revisions',
              'label' => 'Entity Reference Revisions: paragraph:text',
              'handler' => 'default:paragraph',
              'handler_settings' => ['target_bundles' => ['text' => 'text']],
              'field_storage_definition' => $unlimitedvalueFieldStorageDefinition,
            ],
            'field_node_field_entity_reference_revisions_paragraph_clickstream' => [
              'field_type' => 'entity_reference_revisions',
              'label' => 'Entity Reference Revisions: paragraph:clickstream',
              'handler' => 'default:paragraph',
              'handler_settings' => ['target_bundles' => ['clickstream_element' => 'clickstream_element']],
              'field_storage_definition' => $unlimitedvalueFieldStorageDefinition,
            ],
          ],
          'bundles' => [
            'page' => [
              'label' => 'Static Page',
              'fields' => [
                'field_node_text',
                'field_node_field_entity_reference_media_image',
                'field_node_field_entity_reference_revisions_paragraph_text',
              ],
            ],
            'article' => [
              'label' => 'Article',
              'fields' => [
                'field_node_text',
                'field_node_field_entity_reference_media_audio',
                'field_node_field_entity_reference_media_image',
                'field_node_field_entity_reference_revisions_paragraph_text',
                'field_node_field_entity_reference_revisions_paragraph_clickstream',
              ],
            ],
          ],
        ],
        'paragraph' => [
          'label' => 'Paragraph',
          'fields' => [
            'field_paragraph_field_text' => [
              'field_type' => 'string',
              'label' => 'Text',
              'handler' => NULL,
              'handler_settings' => [],
              'field_storage_definition' => $singlevalueFieldStorageDefinition,
            ],
            'field_paragraph_entity_reference_revisions_paragraph_clickstream_item' => [
              'field_type' => 'entity_reference_revisions',
              'label' => 'Entity Reference Revisions: paragraph:clickstream_item',
              'handler' => 'default:paragraph',
              'handler_settings' => ['target_bundles' => ['clickstream_item' => 'clickstream_item']],
              'field_storage_definition' => $singlevalueFieldStorageDefinition,
            ],
            'field_paragraph_entity_reference_media_image' => [
              'field_type' => 'entity_reference',
              'label' => 'Entity Reference: media:image',
              'handler' => 'default:media',
              'handler_settings' => ['target_bundles' => ['image' => 'image']],
              'field_storage_definition' => $singlevalueFieldStorageDefinition,
            ],
            'field_paragraph_entity_reference_media_audio' => [
              'field_type' => 'entity_reference',
              'label' => 'Entity Reference: media:audio',
              'handler' => 'default:media',
              'handler_settings' => ['target_bundles' => ['audio' => 'audio']],
              'field_storage_definition' => $singlevalueFieldStorageDefinition,
            ],
          ],
          'bundles' => [
            'text' => [
              'label' => 'Text Paragraph',
              'fields' => [
                'field_paragraph_field_text',
              ],
            ],
            'audiofile' => [
              'label' => 'Audio File',
              'fields' => [
                'field_paragraph_entity_reference_media_audio',
              ],
            ],
            'clickstream_element' => [
              'label' => 'Clickstream Element',
              'fields' => [
                'field_paragraph_entity_reference_revisions_paragraph_clickstream_item',
              ],
            ],
            'clickstream_item' => [
              'label' => 'Clickstream Item',
              'fields' => [
                'field_paragraph_entity_reference_media_image',
              ],
            ],
          ],
        ],
        'media' => [
          'label' => 'Media',
          'fields' => [],
          'bundles' => [
            'image' => [
              'label' => 'Image',
              'fields' => [],
            ],
            'audio' => [
              'label' => 'Audio',
              'fields' => [],
            ],
          ],
        ],
      ],
      'comparisondata' => [],
    ];

    // Now dynamically define the mock objects
    // according to the structure given above.
    $testEntityTypeOptions = array_combine(
      array_keys($this->testdata['entity_types']),
      array_map(
        function ($item) {
          return $item['label'];
        },
        $this->testdata['entity_types']
      )
    );
    $this->entityTypeRepository->getEntityTypeLabels()->willReturn($testEntityTypeOptions);
    $this->testdata['comparisondata']['entity_type_options'] = $testEntityTypeOptions;
    foreach ($testEntityTypeOptions as $entity_type_id => &$value) {
      $contentEntityTypeInterfaceMock = $this->prophesize(ContentEntityTypeInterface::class);
      $contentEntityTypeInterfaceMock->getLabel()->willReturn($value);
      $value = $contentEntityTypeInterfaceMock->reveal();
      $this->entityTypeManager->getDefinition($entity_type_id)->willReturn($value);
    }
    $this->testdata['comparisondata']['entity_types'] = $testEntityTypeOptions;

    foreach ($this->testdata['entity_types'] as $entity_type_id => &$entity_type_definition) {

      // Dynamically define mocked entity type bundle definitions.
      $testEntityBundles = array_combine(
        array_keys($entity_type_definition['bundles']),
        array_map(
          function ($item) use ($entity_type_definition) {
            return ['label' => $entity_type_definition['bundles'][$item]['label']];
          },
          array_keys($entity_type_definition['bundles'])
        )
      );
      $this->entityBundleInfoService->getBundleInfo($entity_type_id)->willReturn($testEntityBundles);
      $this->testdata['comparisondata']['entity_bundles'][$entity_type_id] = $testEntityBundles;
      $this->testdata['comparisondata']['entity_bundle_options'][$entity_type_id] = array_map(
        function ($item) {
          return $item['label'];
        },
        $testEntityBundles
      );

      // Create mocked fields.
      foreach ($entity_type_definition['fields'] as $field_machine_name => &$field_definition) {
        $this->testdata['comparisondata']['fields'][$field_machine_name] = $field_definition;
        $field = $this->prophesize(FieldConfigInterface::class);
        $field->getLabel()->willReturn($field_definition['label']);
        $field->getName()->willReturn($field_machine_name);
        $field->get('field_type')->willReturn($field_definition['field_type']);
        $field->getSetting('handler')->willReturn($field_definition['handler']);
        $field->getSetting('handler_settings')->willReturn($field_definition['handler_settings']);
        $field->getFieldStorageDefinition()->willReturn($field_definition['field_storage_definition']);
        $field->getTargetEntityTypeId()->willReturn($entity_type_id);
        $field_definition = $field->reveal();
        $this->testdata['comparisondata']['fields'][$field_machine_name]['field_config_interface'] = $field_definition;
      }

      // Assign the created mockfields to the respective bundles.
      foreach ($entity_type_definition['bundles'] as $bundle_machine_name => $bundle_definition) {

        $bundlefields = [
          $base_field_a->getName() => $base_field_a,
          $base_field_b->getName() => $base_field_b,
        ];

        $bundleFieldOptions = [
          $base_field_a->getName() => $base_field_a->getLabel(),
          $base_field_b->getName() => $base_field_b->getLabel(),
        ];

        $bundleFieldsWithoutBaseFields = [];
        $bundleFieldOptionsWithoutBasefields = [];

        foreach ($bundle_definition['fields'] as $field_machine_name) {
          $bundlefields[$field_machine_name] = $entity_type_definition['fields'][$field_machine_name];
          $bundleFieldOptions[$field_machine_name] = $entity_type_definition['fields'][$field_machine_name]->getLabel();
          $bundleFieldsWithoutBaseFields[$field_machine_name] = $entity_type_definition['fields'][$field_machine_name];
          $bundleFieldOptionsWithoutBasefields[$field_machine_name] = $entity_type_definition['fields'][$field_machine_name]->getLabel();
        }

        $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle_machine_name)->willReturn($bundlefields);
        $this->testdata['comparisondata']['entity_bundle_fields'][$entity_type_id][$bundle_machine_name] = $bundlefields;
        $this->testdata['comparisondata']['entity_bundle_fields_without_basefields'][$entity_type_id][$bundle_machine_name] = $bundleFieldsWithoutBaseFields;
        $this->testdata['comparisondata']['entity_bundle_fields_options'][$entity_type_id][$bundle_machine_name] = $bundleFieldOptions;
        $this->testdata['comparisondata']['entity_bundle_fields_options_without_basefields'][$entity_type_id][$bundle_machine_name] = $bundleFieldOptionsWithoutBasefields;
      }

    }

  }

  /**
   * Sets up the class instance to test.
   */
  abstract protected function setUpTestObject();

}
