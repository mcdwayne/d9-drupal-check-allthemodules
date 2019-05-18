<?php

namespace Drupal\Tests\blizz_bulk_creator\Unit\Services;

use Drupal\blizz_bulk_creator\Services\EntityHelper;
use Drupal\Tests\blizz_bulk_creator\Unit\UnitTestBase;
use Drupal\Tests\blizz_bulk_creator\Unit\UnitTestMocksTrait;

/**
 * @coversDefaultClass \Drupal\blizz_bulk_creator\Services\EntityHelper
 * @group blizz_bulk_creator
 */
class EntityHelperTest extends UnitTestBase {

  use UnitTestMocksTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUpTestObject() {
    $this->entityHelper = new EntityHelper(
      $this->entityTypeManager->reveal(),
      $this->entityBundleInfoService->reveal(),
      $this->entityFieldManager->reveal(),
      $this->entityTypeRepository->reveal()
    );
  }

  /**
   * Tests the entityHelper's getContentEntityTypeDefinitions method.
   */
  public function testGetContentEntityTypeDefinitions() {

    // Perform the test.
    $this->assertEquals(
      $this->testdata['comparisondata']['entity_types'],
      $this->entityHelper->getContentEntityTypeDefinitions(),
      'Method ->getContentEntityTypeDefinitions() did not return the expected result'
    );

    // Repeat the test - are the results still valid?
    $this->assertEquals(
      $this->testdata['comparisondata']['entity_types'],
      $this->entityHelper->getContentEntityTypeDefinitions(),
      'Method ->getContentEntityTypeDefinitions() did not return the expected result'
    );

  }

  /**
   * Tests the entityHelper's getEntityTypeOptions method.
   */
  public function testGetEntityTypeOptions() {

    $filteredEntityTypeOptions = array_filter(
      $this->testdata['comparisondata']['entity_type_options'],
      function ($key) {
        return $key != 'media';
      },
      ARRAY_FILTER_USE_KEY
    );

    // Perform the test without parameters.
    $this->assertEquals(
      $filteredEntityTypeOptions,
      $this->entityHelper->getEntityTypeOptions(),
      'Method ->getEntityTypeOptions() did not return the expected result'
    );

    // Now tet the result with parameter override.
    $this->assertEquals(
      $this->testdata['comparisondata']['entity_type_options'],
      $this->entityHelper->getEntityTypeOptions(FALSE),
      'Method ->getEntityTypeOptions() did not return the expected result'
    );

    // Repeat the first test - is it still valid?
    $this->assertEquals(
      $filteredEntityTypeOptions,
      $this->entityHelper->getEntityTypeOptions(),
      'Method ->getEntityTypeOptions() did not return the expected result'
    );

  }

  /**
   * Tests the entityHelper's getEntityBundleDefinitions method.
   */
  public function testGetEntityBundleDefinitions() {

    // First call on "node" entity type id.
    $this->assertEquals(
      $this->testdata['comparisondata']['entity_bundles']['node'],
      $this->entityHelper->getEntityBundleDefinitions('node'),
      'Method ->getEntityBundleDefinitions("node") did not return the expected result'
    );

    // Second call on "node" - does caching work?
    $this->assertEquals(
      $this->testdata['comparisondata']['entity_bundles']['node'],
      $this->entityHelper->getEntityBundleDefinitions('node'),
      'Method ->getEntityBundleDefinitions("node") did return a different result compared to the first try.'
    );

    // First Call on "media".
    $this->assertEquals(
      $this->testdata['comparisondata']['entity_bundles']['media'],
      $this->entityHelper->getEntityBundleDefinitions('media'),
      'Method ->getEntityBundleDefinitions("media") did not return the expected result'
    );

  }

  /**
   * Tests the entityHelper's getEntityBundleOptions method.
   */
  public function testGetEntityBundleOptions() {

    // First call to get node bundle options.
    $this->assertEquals(
      $this->testdata['comparisondata']['entity_bundle_options']['node'],
      $this->entityHelper->getEntityBundleOptions('node'),
      'Method ->getEntityBundleOptions("node") did not return the correct results.'
    );

    // First call to get paragraph bundle options.
    $this->assertEquals(
      $this->testdata['comparisondata']['entity_bundle_options']['paragraph'],
      $this->entityHelper->getEntityBundleOptions('paragraph'),
      'Method ->getEntityBundleOptions("paragraph") did not return the correct results.'
    );

    // First call to get media bundle options.
    $this->assertEquals(
      $this->testdata['comparisondata']['entity_bundle_options']['media'],
      $this->entityHelper->getEntityBundleOptions('media'),
      'Method ->getEntityBundleOptions("media") did not return the correct results.'
    );

    // Are the node results still valid?
    $this->assertEquals(
      $this->testdata['comparisondata']['entity_bundle_options']['node'],
      $this->entityHelper->getEntityBundleOptions('node'),
      'Method ->getEntityBundleOptions("node") did not return the correct results.'
    );

  }

  /**
   * Tests the entityHelper's getBundleFields method.
   */
  public function testGetBundleFields() {

    // First call for node:article.
    $this->assertEquals(
      $this->testdata['comparisondata']['entity_bundle_fields_without_basefields']['node']['article'],
      $this->entityHelper->getBundleFields('node', 'article'),
      'Method ->getBundleFields("node", "article") returned a faulty answer.'
    );

    // Does caching work?
    $this->assertEquals(
      $this->testdata['comparisondata']['entity_bundle_fields_without_basefields']['node']['article'],
      $this->entityHelper->getBundleFields('node', 'article'),
      'Repeated call on method ->getBundleFields("node", "article") returned a faulty answer.'
    );

    // What if base fields should be included?
    $this->assertEquals(
      $this->testdata['comparisondata']['entity_bundle_fields']['node']['article'],
      $this->entityHelper->getBundleFields('node', 'article', TRUE),
      'Call on method ->getBundleFields("node", "article", TRUE) returned a faulty answer.'
    );

    // Does it work for other entities too?
    $this->assertEquals(
      $this->testdata['comparisondata']['entity_bundle_fields_without_basefields']['paragraph']['clickstream_item'],
      $this->entityHelper->getBundleFields('paragraph', 'clickstream_item'),
      'Call on method ->getBundleFields("paragraph", "clickstream_item") returned a faulty answer.'
    );

  }

  /**
   * Tests the entityHelper's getBundleFieldOptions method.
   */
  public function testGetBundleFieldOptions() {

    // Testing node:article field options without base fields.
    $this->assertEquals(
      $this->testdata['comparisondata']['entity_bundle_fields_options_without_basefields']['node']['article'],
      $this->entityHelper->getBundleFieldOptions('node', 'article'),
      'Method ->getBundleFieldOptions("node", "article") returned a faulty answer.'
    );

    // Testing paragraph:clickstream_element field options incl. base fields.
    $this->assertEquals(
      $this->testdata['comparisondata']['entity_bundle_fields_options']['paragraph']['clickstream_element'],
      $this->entityHelper->getBundleFieldOptions('paragraph', 'clickstream_element', TRUE),
      'Method ->getBundleFieldOptions("paragraph", "clickstream_element", TRUE) returned a faulty answer.'
    );

    // Repeating the first test to test caching.
    $this->assertEquals(
      $this->testdata['comparisondata']['entity_bundle_fields_options_without_basefields']['node']['article'],
      $this->entityHelper->getBundleFieldOptions('node', 'article'),
      'Method ->getBundleFieldOptions("node", "article") returned a faulty answer.'
    );

  }

  /**
   * Tests the entityHelper's getReferenceFieldsForTargetBundle method.
   */
  public function testGetReferenceFieldsForTargetBundle() {

    // Just for convenience...
    $testFields = $this->testdata['comparisondata']['fields'];

    // This test generates more complex data to which we will build
    // an array holding the expected results manually.
    $expectedResultForNodeArticleOnMediaImage = [
      'field_node_field_entity_reference_media_image' => (object) [
        'definition' => $testFields['field_node_field_entity_reference_media_image']['field_config_interface'],
        'machine_name' => 'field_node_field_entity_reference_media_image',
        'label' => $testFields['field_node_field_entity_reference_media_image']['label'],
        'host_entity_type' => 'node',
        'target_entity_type' => 'media',
        'target_entity_bundles' => [
          'image' => 'image',
        ],
        'cardinality' => $testFields['field_node_field_entity_reference_media_image']['field_storage_definition']->getCardinality(),
      ],
      'field_node_field_entity_reference_revisions_paragraph_clickstream' => (object) [
        'definition' => $testFields['field_node_field_entity_reference_revisions_paragraph_clickstream']['field_config_interface'],
        'machine_name' => 'field_node_field_entity_reference_revisions_paragraph_clickstream',
        'label' => $testFields['field_node_field_entity_reference_revisions_paragraph_clickstream']['label'],
        'host_entity_type' => 'node',
        'target_entity_type' => 'paragraph',
        'target_entity_bundles' => [
          'clickstream_element' => 'clickstream_element',
        ],
        'cardinality' => $testFields['field_node_field_entity_reference_revisions_paragraph_clickstream']['field_storage_definition']->getCardinality(),
        'children' => [
          'paragraph:clickstream_element' => [
            'field_paragraph_entity_reference_revisions_paragraph_clickstream_item' => (object) [
              'definition' => $testFields['field_paragraph_entity_reference_revisions_paragraph_clickstream_item']['field_config_interface'],
              'machine_name' => 'field_paragraph_entity_reference_revisions_paragraph_clickstream_item',
              'label' => $testFields['field_paragraph_entity_reference_revisions_paragraph_clickstream_item']['label'],
              'host_entity_type' => 'paragraph',
              'target_entity_type' => 'paragraph',
              'target_entity_bundles' => [
                'clickstream_item' => 'clickstream_item',
              ],
              'cardinality' => $testFields['field_paragraph_entity_reference_revisions_paragraph_clickstream_item']['field_storage_definition']->getCardinality(),
              'children' => [
                'paragraph:clickstream_item' => [
                  'field_paragraph_entity_reference_media_image' => (object) [
                    'definition' => $testFields['field_paragraph_entity_reference_media_image']['field_config_interface'],
                    'machine_name' => 'field_paragraph_entity_reference_media_image',
                    'label' => $testFields['field_paragraph_entity_reference_media_image']['label'],
                    'host_entity_type' => 'paragraph',
                    'target_entity_type' => 'media',
                    'target_entity_bundles' => [
                      'image' => 'image',
                    ],
                    'cardinality' => $testFields['field_paragraph_entity_reference_media_image']['field_storage_definition']->getCardinality(),
                  ],
                ],
              ],
            ],
          ],
        ],
      ],
    ];

    $expectedResultForNodePageOnMediaImage = [
      'field_node_field_entity_reference_media_image' => (object) [
        'definition' => $testFields['field_node_field_entity_reference_media_image']['field_config_interface'],
        'machine_name' => 'field_node_field_entity_reference_media_image',
        'label' => $testFields['field_node_field_entity_reference_media_image']['label'],
        'host_entity_type' => 'node',
        'target_entity_type' => 'media',
        'target_entity_bundles' => [
          'image' => 'image',
        ],
        'cardinality' => $testFields['field_node_field_entity_reference_media_image']['field_storage_definition']->getCardinality(),
      ],
    ];

    $expectedResultForNodeArticleOnMediaAudio = [
      'field_node_field_entity_reference_media_audio' => (object) [
        'definition' => $testFields['field_node_field_entity_reference_media_audio']['field_config_interface'],
        'machine_name' => 'field_node_field_entity_reference_media_audio',
        'label' => $testFields['field_node_field_entity_reference_media_audio']['label'],
        'host_entity_type' => 'node',
        'target_entity_type' => 'media',
        'target_entity_bundles' => [
          'audio' => 'audio',
        ],
        'cardinality' => $testFields['field_node_field_entity_reference_media_audio']['field_storage_definition']->getCardinality(),
      ],
    ];

    // Run a test on node:article.
    $this->assertEquals(
      $expectedResultForNodeArticleOnMediaImage,
      $this->entityHelper->getReferenceFieldsForTargetBundle('image', 'node', 'article'),
      'Method ->getReferenceFieldsForTargetBundle("image", "node", "article") did not return the expected result!'
    );

    // Check for media:image on node:page.
    $this->assertEquals(
      $expectedResultForNodePageOnMediaImage,
      $this->entityHelper->getReferenceFieldsForTargetBundle('image', 'node', 'page'),
      'Method ->getReferenceFieldsForTargetBundle("image", "node", "page") did not return the expected result!'
    );

    // Repeat the test on node:article - does caching work?
    $this->assertEquals(
      $expectedResultForNodeArticleOnMediaImage,
      $this->entityHelper->getReferenceFieldsForTargetBundle('image', 'node', 'article'),
      'Method ->getReferenceFieldsForTargetBundle("image", "node", "article") did not return the expected result!'
    );

    // Check media:audio on node:article.
    $this->assertEquals(
      $expectedResultForNodeArticleOnMediaAudio,
      $this->entityHelper->getReferenceFieldsForTargetBundle('audio', 'node', 'article'),
      'Method ->getReferenceFieldsForTargetBundle("audio", "node", "article") did not return the expected result!'
    );

    // There should be no target fields for media:audio on node:page...
    $this->assertEquals(
      [],
      $this->entityHelper->getReferenceFieldsForTargetBundle('audio', 'node', 'page'),
      'Method ->getReferenceFieldsForTargetBundle("audio", "node", "page") did not return the expected result!'
    );

  }

  /**
   * Tests the entityHelper's flattenReferenceFieldsToOptions method.
   */
  public function testFlattenReferenceFieldsToOptions() {

    $tests = [
      (object) [
        'target_bundle' => 'image',
        'entity_type_id' => 'node',
        'entity_bundle' => 'article',
        'expected_result' => [
          'field_node_field_entity_reference_media_image:1' => 'Entity Reference: media:image (Media)',
          'field_node_field_entity_reference_revisions_paragraph_clickstream:-1:paragraph:clickstream_element/field_paragraph_entity_reference_revisions_paragraph_clickstream_item:1:paragraph:clickstream_item/field_paragraph_entity_reference_media_image:1' => 'Entity Reference Revisions: paragraph:clickstream (Paragraph) > Entity Reference Revisions: paragraph:clickstream_item (Paragraph) > Entity Reference: media:image (Media)',
        ],
      ],
      (object) [
        'target_bundle' => 'image',
        'entity_type_id' => 'node',
        'entity_bundle' => 'page',
        'expected_result' => [
          'field_node_field_entity_reference_media_image:1' => 'Entity Reference: media:image (Media)',
        ],
      ],
      (object) [
        'target_bundle' => 'audio',
        'entity_type_id' => 'node',
        'entity_bundle' => 'article',
        'expected_result' => [
          'field_node_field_entity_reference_media_audio:1' => 'Entity Reference: media:audio (Media)',
        ],
      ],
      (object) [
        'target_bundle' => 'audio',
        'entity_type_id' => 'node',
        'entity_bundle' => 'page',
        'expected_result' => [],
      ],
    ];

    foreach ($tests as $test) {
      $fields = $this->entityHelper->getReferenceFieldsForTargetBundle(
        $test->target_bundle,
        $test->entity_type_id,
        $test->entity_bundle
      );
      $this->assertEquals(
        $test->expected_result,
        $this->entityHelper->flattenReferenceFieldsToOptions($fields),
        sprintf(
          'Method ->flattenReferenceFieldsToOptions() did not yield the expected result for target bundle %1$s on %2$s:%3$s.',
          $test->target_bundle,
          $test->entity_type_id,
          $test->entity_bundle
        )
      );
    }

  }

}
