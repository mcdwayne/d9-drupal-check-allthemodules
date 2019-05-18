<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc7;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\FieldConfigInterface;
use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;

/**
 * Tests attribute field instance migration.
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc7
 */
class AttributeFieldInstanceTest extends Ubercart7TestBase {

  use CommerceMigrateTestTrait;


  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'path',
    'commerce_price',
    'commerce_product',
    'commerce_store',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_variation');
    $this->installConfig(['commerce_product']);
    $this->executeMigrations([
      'uc_attribute_field',
      'uc_product_attribute',
      'uc_attribute_field_instance',
    ]);
  }

  /**
   * Asserts various aspects of a field config entity.
   *
   * @param string $name
   *   The field instance machine name.
   * @param string $type
   *   The field type.
   * @param string $bundle
   *   The target bundle.
   * @param string $label
   *   The field label.
   * @param string $description
   *   The field description.
   * @param bool $translatable
   *   Indicates if the field is translatable.
   */
  protected function assertEntity($name, $type, $bundle, $label, $description, $translatable) {
    $id = 'commerce_product_variation.default.attribute_' . $name;
    /** @var \Drupal\field\FieldConfigInterface $field */
    $field = FieldConfig::load($id);
    $this->assertTrue($field instanceof FieldConfigInterface);
    $this->assertSame($type, $field->getType());
    $this->assertSame($bundle, $field->getTargetBundle());
    $this->assertSame($label, $field->label());
    $this->assertSame($description, $field->getDescription());
    $this->assertSame('default:commerce_product_attribute_value', $field->getSetting('handler'));
    $this->assertSame(['target_bundles' => [$name]], $field->getSetting('handler_settings'));
    $this->assertSame('commerce_product_attribute_value', $field->getSetting('target_type'));
    $this->assertEquals($translatable, $field->isTranslatable());
    $this->assertSame('commerce_product_variation', $field->getTargetEntityTypeId());
  }

  /**
   * Test attribute field instance migration.
   */
  public function testAttributeInstance() {
    $this->assertEntity('size', 'entity_reference', 'default', 'Size', 'Volume', TRUE);
    $this->assertEntity('extra', 'entity_reference', 'default', 'Extra', '', TRUE);
    $this->assertEntity('attribute_with_name_', 'entity_reference', 'default', 'Long name test', '', TRUE);
    $this->assertEntity('duration', 'entity_reference', 'default', 'Duration', '', TRUE);
  }

}
