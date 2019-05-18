<?php

namespace Drupal\Tests\dream_fields\Kernel;

use Drupal\dream_fields\FieldBuilderInterface;
use Drupal\dream_fields\FieldCreationManager;
use Drupal\dream_fields\FieldCreator;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;

/**
 * Test the field creation service.
 *
 * @group dream_fields
 */
class FieldBuilderTest extends FieldKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['dream_fields', 'field_ui', 'link'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['field_ui']);
    $this->fieldCreator = $this->container->get('dream_fields.field_creator');
  }

  /**
   * Test the field creation.
   *
   * @dataProvider fieldBuilderTestCases
   */
  public function testFieldBuilder(FieldBuilderInterface $field_builder, $expected_field_name, $expected_type, $expected_cardinality) {
    $field_builder
      ->setEntityTypeId('entity_test')
      ->setBundle('entity_test');

    $this->fieldCreator->save($field_builder);

    // Load the field configuration and assert the values were set and saved.
    $created_field = FieldConfig::loadByName('entity_test', 'entity_test', $expected_field_name);
    $created_storage = FieldStorageConfig::loadByName('entity_test', $expected_field_name);

    $this->assertNotEmpty($created_field);
    $this->assertNotEmpty($created_storage);
    $this->assertEquals($expected_type, $created_field->getType());
    $this->assertEquals($expected_cardinality, $created_storage->getCardinality());
  }

  /**
   * Data provider for ::testFieldBuilder
   */
  public function fieldBuilderTestCases() {
    return [
      'Standard field creation' => [
        FieldCreator::createBuilder()
          ->setLabel('Foo')
          ->setField('text'),
        'field_foo',
        'text',
        1,
      ],
      'Multi-cardinality link field' => [
        FieldCreator::createBuilder()
          ->setLabel('Bar')
          ->setField('link', [], [])
          ->setCardinality(-1),
        'field_bar',
        'link',
        -1,
      ],
      'Field with custom widget' => [
        FieldCreator::createBuilder()
          ->setLabel('Entity Reference Widget')
          ->setField('entity_reference', [], [])
          ->setWidget('options_select'),
        'field_entity_reference_widget',
        'entity_reference',
        1,
      ],
      'Field with custom formatter' => [
        FieldCreator::createBuilder()
          ->setLabel('Trimmed Text')
          ->setField('text')
          ->setDisplay('text_trimmed'),
        'field_trimmed_text',
        'text',
        1,
      ],
    ];
  }

}
