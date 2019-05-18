<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc6;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\FieldConfigInterface;
use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;

/**
 * Tests attribute field instance migration.
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc6
 */
class AttributeFieldInstanceTest extends Ubercart6TestBase {

  use CommerceMigrateTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_price',
    'commerce_product',
    'commerce_store',
    'path',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->migrateAttributes();
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
    $this->assertEntity('design', 'entity_reference', 'default', 'Cool Designs for your towel', 'Select a design', TRUE);
    $this->assertEntity('color', 'entity_reference', 'default', 'Color', 'Available towel colors', TRUE);
    $this->assertEntity('model_size_attribute', 'entity_reference', 'default', 'Model size', 'Select your starship model size.', TRUE);
    $this->assertEntity('name', 'entity_reference', 'default', 'Name', 'Enter a name to be written on the cake.', TRUE);
  }

}
