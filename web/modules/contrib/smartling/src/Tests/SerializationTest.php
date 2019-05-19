<?php

namespace Drupal\smartling\Tests;

use Drupal\simpletest\KernelTestBase;

/**
 * Class SerializationTest.
 *
 * @group smartling
 */
class SerializationTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('serialization', 'system', 'field', 'options', 'entity_test', 'text', 'filter', 'user', 'smartling');

  /**
   * The test values.
   *
   * @var array
   */
  protected $values;

  /**
   * The test entity.
   *
   * @var \Drupal\Core\Entity\ContentEntityBase
   */
  protected $entity;

  /**
   * The test user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * The serializer service.
   *
   * @var \Symfony\Component\Serializer\Serializer.
   */
  protected $serializer;

  /**
   * The class name of the test class.
   *
   * @var string
   */
  protected $entityClass = 'Drupal\entity_test\Entity\EntityTest';

  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test_mulrev');
    $this->installEntitySchema('user');
    $this->installEntitySchema('smartling_submission');
    $this->installConfig(array('field', 'smartling'));
    \Drupal::moduleHandler()->invoke('rest', 'install');

    // Auto-create a field for testing.
    entity_create('field_storage_config', array(
      'entity_type' => 'entity_test_mulrev',
      'field_name' => 'field_test_text_translatable',
      'type' => 'text',
      'cardinality' => 1,
      'translatable' => TRUE,
    ))->save();
    entity_create('field_config', array(
      'entity_type' => 'entity_test_mulrev',
      'field_name' => 'field_test_text_translatable',
      'bundle' => 'entity_test_mulrev',
      'label' => 'Test text-field',
      'widget' => array(
        'type' => 'text_textfield',
        'weight' => 0,
      ),
    ))->save();
    // Auto-create a field for testing.
    entity_create('field_storage_config', array(
      'entity_type' => 'entity_test_mulrev',
      'field_name' => 'field_test_text_untranslatable',
      'type' => 'text',
      'cardinality' => 1,
      'translatable' => FALSE,
    ))->save();
    entity_create('field_config', array(
      'entity_type' => 'entity_test_mulrev',
      'field_name' => 'field_test_text_untranslatable',
      'bundle' => 'entity_test_mulrev',
      'label' => 'Test text-field',
      'widget' => array(
        'type' => 'text_textfield',
        'weight' => 0,
      ),
    ))->save();
    // Auto-create a field for testing.
    entity_create('field_storage_config', array(
      'entity_type' => 'entity_test_mulrev',
      'field_name' => 'field_test_text_translated',
      'type' => 'text',
      'cardinality' => 2,
      'translatable' => TRUE,
    ))->save();
    entity_create('field_config', array(
      'entity_type' => 'entity_test_mulrev',
      'field_name' => 'field_test_text_translated',
      'bundle' => 'entity_test_mulrev',
      'label' => 'Test text-field',
      'widget' => array(
        'type' => 'text_textfield',
        'weight' => 0,
      ),
    ))->save();

    // User create needs sequence table.
    $this->installSchema('system', array('sequences'));

    // Create a test user to use as the entity owner.
    $this->user = \Drupal::entityTypeManager()->getStorage('user')->create([
      'name' => 'serialization_test_user',
      'mail' => 'foo@example.com',
      'pass' => '123456',
    ]);
    $this->user->save();

    // Create a test entity to serialize.
    $this->values = array(
      'name' => $this->randomMachineName(),
      'user_id' => $this->user->id(),
      'field_test_text_untranslatable' => array(
        'value' => $this->randomMachineName(),
        'format' => 'full_html',
      ),
      'field_test_text_translatable' => array(
        'value' => $this->randomMachineName(),
        'format' => 'full_html',
      ),
      'field_test_text_translated' => array(
        [
          'value' => $this->randomMachineName(),
          'format' => 'full_html',
        ],
        [
          'value' => $this->randomMachineName(),
          'format' => 'full_html',
        ],
      ),
    );
    $this->entity = entity_create('entity_test_mulrev', $this->values);
    $this->entity->save();

    $this->serializer = $this->container->get('serializer');
  }

  public function testEncoder() {
    $this->doNormalize();
    $this->doDenormalize();
    $this->doDeserializeEntity();
    $this->doSerializeEntity();
  }

  /**
   * Test the normalize function.
   */
  protected function doNormalize() {
    $expected = [
      [
        '@field_name' => 'name',
        'field_item' => [
          [
            'value' => $this->values['name'],
          ],
        ],
      ],
      // @todo this has not be here.
      [
        '@field_name' => 'field_test_text_translatable',
        'field_item' => [
          $this->values['field_test_text_translatable'],
        ],
      ],
      [
        '@field_name' => 'field_test_text_translated',
        'field_item' => [
          $this->values['field_test_text_translated'][0],
          $this->values['field_test_text_translated'][1],
        ],
      ],
    ];

    $normalized = $this->serializer->normalize($this->entity, 'smartling_xml');
    foreach (array_keys($expected) as $fieldName) {
      $this->assertEqual($expected[$fieldName], $normalized[$fieldName], "ComplexDataNormalizer produces expected array for $fieldName.");
    }
    $this->assertEqual(array_diff_key($normalized, $expected), array(), 'No unexpected data is added to the normalized array.');
  }

  protected function doDenormalize() {
    $normalized = $this->serializer->normalize($this->entity, 'smartling_xml');

    $denormalized = $this->serializer->denormalize($normalized, $this->entityClass, 'smartling_xml', [
      'entity_type' => 'entity_test_mulrev',
      'bundle_value' => 'entity_test_mulrev',
      'bundle_key' => 'type',
    ]);
    $this->assertIdentical($denormalized->getEntityTypeId(), $this->entity->getEntityTypeId(), 'Expected entity type found.');
    $this->assertIdentical($denormalized->bundle(), $this->entity->bundle(), 'Expected entity bundle found.');
    $this->assertIdentical($this->values['name'], $this->entity->name->value, 'Expected entity bundle found.');
    $this->assertIdentical($this->values['field_test_text_translatable']['value'], $this->entity->field_test_text_translatable->value);
    $this->assertIdentical($this->values['field_test_text_translatable']['format'], $this->entity->field_test_text_translatable->format);
    $this->assertIdentical($this->values['field_test_text_translated'][0]['value'], $this->entity->field_test_text_translated[0]->value);
    $this->assertIdentical($this->values['field_test_text_translated'][1]['value'], $this->entity->field_test_text_translated[1]->value);
  }

  protected function doSerializeEntity() {
    $actual = trim(str_replace("\n", '', $this->serializer->serialize($this->entity, 'smartling_xml')));
    $expected = [
      '<?xml version="1.0"?>',
      '<!--smartling.translate_paths = document/item/field_item/value, document/item/field_item/summary-->',
      '<!--smartling.string_format_paths = html : document/item/field_item/value-->',
      '<!--smartling.placeholder_format_custom = (@|%|!)[\\w-]+-->',
      '<document>',
      '<item key="0" field_name="name"><field_item><value>' . $this->values['name'] . '</value></field_item></item>',
      '<item key="1" field_name="field_test_text_translatable"><field_item><value>' . $this->values['field_test_text_translatable']['value'] . '</value><format>full_html</format></field_item></item>',
      '<item key="2" field_name="field_test_text_translated"><field_item><value>' . $this->values['field_test_text_translated'][0]['value'] . '</value><format>full_html</format></field_item>' .
      '<field_item><value>' . $this->values['field_test_text_translated'][1]['value'] . '</value><format>full_html</format></field_item></item>',
      '</document>',
    ];
    // Reduced the array to a string.
    $expected = trim(str_replace("\n", '', implode('', $expected)));
    $this->assertEqual($expected, $actual);
  }

  protected function doDeserializeEntity() {
    $xml = [
      '<?xml version="1.0"?>',
      '<!--smartling.translate_paths = document/item/field_item/value, document/item/field_item/summary-->',
      '<!--smartling.string_format_paths = html : document/item/field_item/value-->',
      '<!--smartling.placeholder_format_custom = (@|%|!)[\\w-]+-->',
      '<document>',
      '<item key="0" field_name="name"><field_item><value>' . $this->values['name'] . '</value></field_item></item>',
      '<item key="1" field_name="field_test_text_translatable"><field_item><value>' . $this->values['field_test_text_translatable']['value'] . '</value><format>full_html</format></field_item></item>',
      '<item key="2" field_name="field_test_text_translated"><field_item><value>' . $this->values['field_test_text_translated'][0]['value'] . '</value><format>full_html</format></field_item>' .
      '<field_item><value>' . $this->values['field_test_text_translated'][1]['value'] . '</value><format>full_html</format></field_item></item>',
      '</document>',
    ];
    $xml = implode('', $xml);

    $deserialized = $this->serializer->deserialize($xml, $this->entityClass, 'smartling_xml', [
      'entity_type' => 'entity_test_mulrev',
      'bundle_value' => 'entity_test_mulrev',
      'bundle_key' => 'type',
    ]);

    $this->assertEqual($this->entity->name->value, $deserialized->name->value);
    $this->assertEqual($this->entity->field_test_text_translatable->value, $deserialized->field_test_text_translatable->value);
    $this->assertEqual($this->entity->field_test_text_translatable->format, $deserialized->field_test_text_translatable->format);
    $this->assertEqual($this->entity->field_test_text_translated->value, $deserialized->field_test_text_translated->value);
  }

}
