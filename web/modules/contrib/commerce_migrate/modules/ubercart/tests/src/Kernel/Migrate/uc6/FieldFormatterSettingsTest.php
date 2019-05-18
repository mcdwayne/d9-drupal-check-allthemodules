<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc6;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;

/**
 * Migrate field instance tests.
 *
 * @requires module migrate_plus
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc6
 */
class FieldFormatterSettingsTest extends Ubercart6TestBase {

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
   * Asserts various aspects of a view display.
   *
   * @param string $id
   *   The view display ID.
   */
  protected function assertEntity($id) {
    $display = EntityViewDisplay::load($id);
    $this->assertInstanceOf(EntityViewDisplayInterface::class, $display);
  }

  /**
   * Asserts various aspects of a view display.
   *
   * @param string $id
   *   The view display ID.
   */
  protected function assertNotEntity($id) {
    $display = EntityViewDisplay::load($id);
    $this->assertNull($display);
  }

  /**
   * Asserts various aspects of a particular component of a view display.
   *
   * @param string $display_id
   *   The view display ID.
   * @param string $component_id
   *   The component ID.
   * @param string $type
   *   The expected component type (formatter plugin ID).
   * @param string $label
   *   The expected label of the component.
   * @param int $weight
   *   The expected weight of the component.
   */
  protected function assertComponent($display_id, $component_id, $type, $label, $weight) {
    $component = EntityViewDisplay::load($display_id)->getComponent($component_id);
    $this->assertTrue(is_array($component));
    $this->assertSame($type, $component['type']);
    $this->assertSame($label, $component['label']);
    $this->assertSame($weight, $component['weight']);
  }

  /**
   * Test that product entity display are migrated to product entities.
   */
  public function testEntityDisplaySettings() {
    $this->assertNotEntity('node.ship.default');
    $this->assertNotEntity('node.product.default');
    $this->assertNotEntity('commerce_product.page.default');
    $this->assertNotEntity('commerce_product.story.default');

    $this->assertEntity('commerce_product.default.default');
    $this->assertComponent('commerce_product.default.default', 'body', 'text_default', 'hidden', 0);
    $this->assertComponent('commerce_product.default.default', 'variations', 'commerce_add_to_cart', 'hidden', 1);
    $this->assertComponent('commerce_product.default.default', 'title', 'string', 'hidden', -5);

    $this->assertEntity('commerce_product.product.default');
    $this->assertComponent('commerce_product.product.default', 'body', 'text_default', 'hidden', -4);
    $this->assertComponent('commerce_product.product.default', 'variations', 'commerce_add_to_cart', 'above', 10);
    $this->assertComponent('commerce_product.product.default', 'field_integer', 'number_integer', 'above', 33);
    $this->assertComponent('commerce_product.product.default', 'field_sustain', 'text_default', 'above', 31);

    $this->assertEntity('commerce_product.ship.default');
    $this->assertComponent('commerce_product.ship.default', 'body', 'text_default', 'hidden', -4);
    $this->assertComponent('commerce_product.ship.default', 'variations', 'commerce_add_to_cart', 'above', 10);
    $this->assertComponent('commerce_product.ship.default', 'field_integer', 'number_integer', 'above', 33);
    $this->assertComponent('commerce_product.ship.default', 'field_engine', 'text_default', 'above', 31);
  }

}
