<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc7;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;

/**
 * Tests migration of Ubercart 7 field formatter settings.
 *
 * @requires module migrate_plus
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc7
 */
class FieldFormatterSettingsTest extends Ubercart7TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'comment',
    'commerce_price',
    'commerce_product',
    'commerce_store',
    'file',
    'image',
    'migrate_plus',
    'node',
    'path',
    'taxonomy',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->migrateFields();
    $this->executeMigrations([
      'd7_view_modes',
      'd7_field_formatter_settings',
    ]);
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
    $this->assertNotEntity('node.product.default');
    $this->assertNotEntity('commerce_product.page.default');
    $this->assertNotEntity('commerce_product.article.default');

    $this->assertEntity('node.article.default');
    $this->assertComponent('node.article.default', 'field_image', 'image', 'hidden', -1);
    $this->assertComponent('node.article.default', 'field_tags', 'entity_reference_label', 'above', 10);

    $this->assertEntity('commerce_product.default.default');
    $this->assertComponent('commerce_product.default.default', 'body', 'text_default', 'hidden', 0);
    $this->assertComponent('commerce_product.default.default', 'variations', 'commerce_add_to_cart', 'hidden', 1);
    $this->assertComponent('commerce_product.default.default', 'title', 'string', 'hidden', -5);

    $this->assertEntity('commerce_product.product.default');
    $this->assertComponent('commerce_product.product.default', 'body', 'text_default', 'hidden', 0);
    $this->assertComponent('commerce_product.product.default', 'variations', 'commerce_add_to_cart', 'above', 10);
    $this->assertComponent('commerce_product.product.default', 'field_number', 'number_integer', 'above', 3);
    $this->assertComponent('commerce_product.product.default', 'field_sustainability', 'string', 'above', 4);
    $this->assertComponent('commerce_product.product.default', 'taxonomy_catalog', 'entity_reference_label', 'above', 2);
    $this->assertComponent('commerce_product.product.default', 'uc_product_image', 'image', 'above', 1);

    $this->assertEntity('taxonomy_term.catalog.default');
    $this->assertComponent('taxonomy_term.catalog.default', 'uc_catalog_image', 'image', 'above', 0);
  }

}
