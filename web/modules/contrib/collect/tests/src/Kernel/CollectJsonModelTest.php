<?php

namespace Drupal\Tests\collect\Kernel;

use Drupal\collect\Entity\Container;
use Drupal\collect\Entity\Model;
use Drupal\collect\Plugin\collect\Model\FieldDefinition;
use Drupal\collect\Model\PropertyDefinition;
use Drupal\Component\Serialization\Json;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * Tests features of the CollectJSON format.
 *
 * @group collect
 */
class CollectJsonModelTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'collect',
    'collect_common',
    'field',
    'user',
    'hal',
    'serialization',
    'rest',
    'system',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['collect']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('collect_container');
    $this->installSchema('system', ['router', 'sequences']);
    \Drupal::service('router.builder')->rebuild();
  }

  /**
   * Tests that field definitions are saved to a separate container.
   */
  public function testCaptureSeparateFieldsContainer() {
    /** @var \Drupal\collect\CaptureEntity $entity_capturer */
    $entity_capturer = \Drupal::service('collect.capture_entity');

    // Create a user and capture it into a container.
    $first_user = User::create([
      'name' => 'First',
      'mail' => 'first@example.com',
    ]);
    $first_user->save();

    $values1 = $entity_capturer->captureEntityInsert($first_user);
    $field_schema_uri = FieldDefinition::URI;

    // One fields container and one values container should be created.
    $containers = Container::loadMultiple();
    $this->assertEqual(count($containers), 2);
    /** @var \Drupal\collect\CollectContainerInterface $fields_container */
    foreach ($containers as $container) {
      if ($container->getSchemaUri() == $field_schema_uri) {
        $fields_container = $container;
      }
    }

    $this->assertEqual($fields_container->getSchemaUri(), FieldDefinition::URI);
    $this->assertEqual($fields_container->getOriginUri(), 'http://schema.md-systems.ch/collect/0.0.1/collectjson/' . \Drupal::request()->getHttpHost() . '/entity/user');
    $this->setRawContent($fields_container->getData());
    $this->assertRaw('"fields":');
    $this->assertNoRaw('"values":');

    $this->assertEqual($values1->getSchemaUri(), $fields_container->getOriginUri());
    $this->assertEqual($values1->getOriginUri(), $first_user->url('canonical', ['absolute' => TRUE]));
    $this->setRawContent($values1->getData());
    $this->assertNoRaw('"fields":');
    $this->assertRaw('"values":');

    // Capturing may modify the loaded fields container entity, so save some
    // current field values for comparison.
    $fields_container_vid = $fields_container->getRevisionId();
    $fields_container_data = $fields_container->getData();

    // Create another user and capture it.
    $second_user = User::create([
      'name' => 'Second',
      'mail' => 'second@example.com',
    ]);
    $second_user->save();
    $values2 = $entity_capturer->captureEntityInsert($second_user);

    // Only the values container should be created.
    $this->assertEqual(count(Container::loadMultiple()), 3);
    $this->setRawContent($values2->getData());
    $this->assertNoRaw('"fields":');
    $this->assertRaw('"values":');
    // The fields container should not be updated.
    $this->assertEqual($fields_container_vid, $fields_container->getRevisionId());
    $this->assertEqual($fields_container_data, $fields_container->getData(), 'Container data is unchanged.');

    // Add a field to the user.
    FieldStorageConfig::create([
      'field_name' => 'field_cake',
      'type' => 'string',
      'entity_type' => 'user',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_cake',
      'field_type' => 'string',
      'entity_type' => 'user',
      'bundle' => 'user',
      'label' => 'Favorite cake',
    ])->save();

    // Create another user and capture it.
    $third_user = User::create([
      'name' => 'Third',
      'mail' => 'third@example.com',
      'field_cake' => 'Black Forest Cake',
    ]);
    $third_user->save();
    $entity_capturer->captureEntityInsert($third_user);

    // The fields container should be updated.
    $containers = Container::loadMultiple();
    $fields_container = Container::load($fields_container->id());
    $this->assertEqual(count($containers), 4);
    $this->assertNotEqual($fields_container_vid, $fields_container->getRevisionId());
    $this->assertNotEqual($fields_container_data, $fields_container->getData(), 'Container data has been changed.');
    $this->setRawContent($fields_container->getData());
    $this->assertRaw('Favorite cake');
  }

  /**
   * Tests that combined values/fields are still supported.
   */
  public function testCombinedContainer() {
    // Create a container with fields and values.
    /** @var \Drupal\collect\Entity\Container $container */
    $container = Container::create([
      'schema_uri' => 'schema:test',
      'data' => Json::encode([
        'fields' => [
          // Basic string.
          'dog' => [
            'type' => 'string',
            'label' => 'Dog',
          ],
          // String field item.
          'fish' => [
            'type' => 'field_item',
            'field_type' => 'string',
            'field_name' => 'fish',
            'entity_type' => 'animals',
            'bundle' => 'pets',
            'label' => 'Fish',
            'description' => 'Swimming thing',
            'required' => FALSE,
            'translatable' => FALSE,
            'settings' => [],
            'storage' => [
              'cardinality' => 1,
              'custom_storage' => FALSE,
              'field_name' => 'fish',
              'provider' => 'animals',
              'queryable' => FALSE,
              'revisionable' => FALSE,
              'settings' => [],
              'entity_type' => 'animals',
              'translatable' => FALSE,
              'type' => 'field_item',
            ],
          ],
        ],
        'entity_type' => 'test',
        'values' => [
          'dog' => 'Terrier',
          'fish' => [
            [
              'value' => 'Tuna',
            ]
          ],
        ],
      ]),
    ]);
    $container->save();

    // Apply the CollectJSON model plugin.
    $model = Model::create([
      'id' => 'pets',
      'uri_pattern' => 'schema:test',
      'plugin_id' => 'collectjson',
      'container_revision' => TRUE,
      'label' => 'Pet collection',
    ]);
    $model->save();

    // Values should be available.
    /** @var \Drupal\collect\TypedData\TypedDataProvider $typed_data_provider */
    $typed_data_provider = \Drupal::service('collect.typed_data_provider');
    $data = $typed_data_provider->getTypedData($container);

    $dog = $data->get('dog')->getString();
    $this->assertEqual($dog, 'Terrier');

    $fish = $data->get('fish')->getString();
    $this->assertEqual($fish, 'Tuna');

    // Defined but non-existing value should return empty.
    $model->setTypedProperty('carrot', new PropertyDefinition('carrot', DataDefinition::create('string')))->save();
    $data = $typed_data_provider->getTypedData($container);
    $this->assertNull($data->get('carrot')->getValue());
  }

}
