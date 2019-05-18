<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc6;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Display\EntityFormDisplayInterface;

/**
 * Migrate field widget settings tests.
 *
 * @requires module migrate_plus
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc6
 */
class FieldWidgetSettingsTest extends Ubercart6TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_price',
    'commerce_product',
    'commerce_store',
    'field',
    'migrate_plus',
    'path',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('commerce_product');
    $this->installConfig(['commerce_product']);
    $this->migrateFields();
  }

  /**
   * Asserts various aspects of a form display entity.
   *
   * @param string $id
   *   The entity ID.
   * @param string $expected_entity_type
   *   The expected entity type to which the display settings are attached.
   * @param string $expected_bundle
   *   The expected bundle to which the display settings are attached.
   */
  protected function assertEntity($id, $expected_entity_type, $expected_bundle) {
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $entity */
    $entity = EntityFormDisplay::load($id);
    $this->assertInstanceOf(EntityFormDisplayInterface::class, $entity);
    $this->assertSame($expected_entity_type, $entity->getTargetEntityTypeId());
    $this->assertSame($expected_bundle, $entity->getTargetBundle());
  }

  /**
   * Asserts various aspects of a particular component of a form display.
   *
   * @param string $display_id
   *   The form display ID.
   * @param string $component_id
   *   The component ID.
   * @param string $widget_type
   *   The expected widget type.
   * @param string $weight
   *   The expected weight of the component.
   */
  protected function assertComponent($display_id, $component_id, $widget_type, $weight) {
    $component = EntityFormDisplay::load($display_id)->getComponent($component_id);
    $this->assertTrue(is_array($component));
    $this->assertSame($widget_type, $component['type']);
    $this->assertSame($weight, $component['weight']);
  }

  /**
   * Test that commerce_product settings are created.
   */
  public function testWidgetSettings() {
    $this->assertEntity('node.page.default', 'node', 'page');
    $this->assertComponent('node.page.default', 'body', 'text_textarea_with_summary', 121);
    $this->assertComponent('node.page.default', 'field_integer', 'number', 31);

    $this->assertEntity('commerce_product.default.default', 'commerce_product', 'default');

    $this->assertComponent('commerce_product.product.default', 'field_image_cache', 'image_image', -2);
    $this->assertComponent('commerce_product.product.default', 'field_integer', 'number', 33);
    $this->assertComponent('commerce_product.product.default', 'field_sustain', 'text_textarea', 31);

    $this->assertComponent('commerce_product.ship.default', 'field_engine', 'text_textarea', 31);
    $this->assertComponent('commerce_product.ship.default', 'field_image_cache', 'image_image', -2);
    $this->assertComponent('commerce_product.ship.default', 'field_integer', 'number', 33);
  }

}
