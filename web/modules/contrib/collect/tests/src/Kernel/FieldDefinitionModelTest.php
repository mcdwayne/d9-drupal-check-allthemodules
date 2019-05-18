<?php

namespace Drupal\Tests\collect\Kernel;

use Drupal\collect\Entity\Container;
use Drupal\collect\Plugin\collect\Model\FieldDefinition;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests features of the Collect Field Definition model plugin.
 *
 * @group collect
 */
class FieldDefinitionModelTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'collect',
    'collect_common',
    'hal',
    'rest',
    'serialization',
    'system',
    'views',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('collect_container');
    $this->installConfig(['collect']);
  }

  /**
   * Tests the properties of the model plugin.
   */
  public function testProperties() {
    $field_container = Container::create([
      'data' => Json::encode([
        'fields' => [
          'nid' => [
            'type' => 'field_item',
            'field_type' => 'integer',
            'field_name' => 'nid',
            'entity_type' => 'node',
            'bundle' => 'article',
            'label' => 'Node ID',
            'description' => 'The Node ID',
            'required' => FALSE,
            'translatable' => FALSE,
            'settings' => [],
            'storage' => [
              'cardinality' => 1,
              'custom_storage' => FALSE,
              'field_name' => 'nid',
              'provider' => 'node',
              'queryable' => FALSE,
              'revisionable' => FALSE,
              'settings' => [],
              'entity_type' => 'node',
              'translatable' => FALSE,
              'type' => 'integer',
            ],
          ],
        ],
      ]),
      'schema_uri' => FieldDefinition::URI,
      'type' => 'application/json',
    ]);
    $field_container->save();

    /** @var \Drupal\collect\TypedData\TypedDataProvider $typed_data_provider */
    $typed_data_provider = \Drupal::service('collect.typed_data_provider');
    $data = $typed_data_provider->getTypedData($field_container);
    /** @var \Drupal\Core\Field\BaseFieldDefinition $nid_definition */
    $nid_definition = $data->get('fields')->getValue()['nid'];
    $this->assertTrue($nid_definition instanceof DataDefinitionInterface);
    $this->assertTrue($nid_definition instanceof FieldDefinitionInterface);
    $this->assertEqual('Node ID', $nid_definition->getLabel());
    $this->assertEqual('integer', $nid_definition->getType());
    $this->assertEqual('The Node ID', $nid_definition->getDescription());

    // Accessing property with no value should return empty.
    $field_container->setData(Json::encode(['fields' => []]));
    $field_container->save();
    $data = $typed_data_provider->getTypedData($field_container);
    $fields = $data->get('fields')->getValue();
    $this->assertTrue(empty($fields));
  }
}
