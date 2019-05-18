<?php

namespace Drupal\Tests\hn\Functional;

use Drupal\node\Entity\Node;

/**
 * Provides some basic tests with permissions of the HN module.
 *
 * @group hn_cleaner
 */
class HnStripFieldsTest extends HnFunctionalTestBase {

  public static $modules = [
    'hn_cleaner',
  ];

  /**
   * The internal node url.
   *
   * @var string
   */
  private $nodeUrl;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  private $entityFieldManager;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $test_node = Node::create([
      'type' => 'hn_test_basic_page',
      'title' => 'Test node',
    ]);

    $test_node->save();

    // We get the internal path to exclude the subdirectory the Drupal is
    // installed in.
    $this->nodeUrl = $test_node->toUrl()->getInternalPath();

    $this->entityFieldManager = \Drupal::service('entity_field.manager');
  }

  /**
   * Removes internal fields from a list of field definitions.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface[] $fieldDefinitions
   *   The list of field definitions to filter.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   The filtered list of field definitions.
   */
  private function onlyExternalFieldDefinitions(array $fieldDefinitions) {
    return array_filter($fieldDefinitions, function ($fieldDefinition) {
      /** @var \Drupal\Core\Field\FieldDefinitionInterface $fieldDefinition */
      return !$fieldDefinition->isInternal();
    });
  }

  /**
   * Assure all fields are still available with the default unchanged.
   */
  public function testWithoutChangingConfig() {
    $response = $this->getHnJsonResponse($this->nodeUrl);

    $existingFields = $this->entityFieldManager->getFieldDefinitions('node', 'hn_test_basic_page');
    $existingFields = $this->onlyExternalFieldDefinitions($existingFields);
    $existingFields['__hn'] = TRUE;

    $availableFields = $response['data'][$response['paths'][$this->nodeUrl]]
      + ['field_teaser_body' => TRUE];

    // When comparing all the available details, they should be the same.
    $this->assertEquals([], array_diff_key($existingFields, $availableFields));
    $this->assertEquals([], array_diff_key($availableFields, $existingFields));
  }

  /**
   * Assure the fields from config are stripped from the response.
   */
  public function testFieldStrip() {

    // Set the config to remove a few fields of nodes.
    $config = \Drupal::configFactory()->getEditable('hn_cleaner.settings');
    $keysToStrip = [
      'nid', 'uuid', 'vid', 'langcode', 'type', 'status', 'uid', 'created',
      'changed', 'promote', 'sticky', 'revision_timestamp', 'revision_uid',
      'revision_log', 'revision_translation_affected', 'default_langcode',
      'path',
    ];

    // We add a key to the config that doesn't exist, to make sure it doesn't
    // crash. See issue #2916488.
    $config->set('fields', [
      'node' => array_merge($keysToStrip, ['this_key_doesnt_exist']),
    ]);
    $config->save();

    // Get the response.
    $response = $this->getHnJsonResponse($this->nodeUrl);

    $existingFields = $this->entityFieldManager->getFieldDefinitions('node', 'hn_test_basic_page');
    $existingFields = $this->onlyExternalFieldDefinitions($existingFields);
    $existingFields['__hn'] = TRUE;
    $availableFields = $response['data'][$response['paths'][$this->nodeUrl]]
      + ['field_teaser_body' => TRUE];

    $keysThatShouldBeStripped = array_keys(array_diff_key($existingFields, $availableFields));
    sort($keysToStrip);
    sort($keysThatShouldBeStripped);

    // When comparing the fields, the $keysToStrip should be removed.
    $this->assertEquals($keysToStrip, $keysThatShouldBeStripped);
    $this->assertEquals([], array_diff_key($availableFields, $existingFields));
  }

  /**
   * Assure the fields from config are stripped from the response.
   */
  public function testEntityStrip() {

    // Set the config to remove all nodes.
    $config = \Drupal::configFactory()->getEditable('hn_cleaner.settings');
    $config->set('entities', ['node']);
    $config->save();

    // Get the response.
    $response = $this->getHnJsonResponse($this->nodeUrl);

    // The data endpoint should only contain the __hn => status property.
    $entity_data = $response['data'][$response['paths'][$this->nodeUrl]];
    $this->assertEquals(['__hn' => ['status' => 200]], $entity_data);
  }

}
