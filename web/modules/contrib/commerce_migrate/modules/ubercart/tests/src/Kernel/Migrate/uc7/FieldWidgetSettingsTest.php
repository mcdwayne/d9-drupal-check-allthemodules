<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc7;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Display\EntityFormDisplayInterface;

/**
 * Tests migration of Ubercart 7 field widget settings.
 *
 * @requires module migrate_plus
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc7
 */
class FieldWidgetSettingsTest extends Ubercart7TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'comment',
    'commerce_price',
    'commerce_product',
    'commerce_store',
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
      'd7_field_instance_widget_settings',
    ]);
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
    $this->assertComponent('node.page.default', 'body', 'text_textarea_with_summary', -4);
    $this->assertComponent('node.page.default', 'field_number', 'number', -3);

    $this->assertEntity('node.article.default', 'node', 'article');
    $this->assertComponent('node.article.default', 'field_image', 'image_image', -1);
    $this->assertComponent('node.article.default', 'field_tags', 'entity_reference_autocomplete', -4);

    $this->assertEntity('commerce_product.default.default', 'commerce_product', 'default');

    $this->assertEntity('commerce_product.product.default', 'commerce_product', 'product');
    $this->assertComponent('commerce_product.product.default', 'field_sustainability', 'string_textfield', 1);
    $this->assertComponent('commerce_product.product.default', 'taxonomy_catalog', 'options_select', -2);
    $this->assertComponent('commerce_product.product.default', 'uc_product_image', 'image_image', -3);

    $this->assertEntity('taxonomy_term.catalog.default', 'taxonomy_term', 'catalog');
    $this->assertComponent('taxonomy_term.catalog.default', 'uc_catalog_image', 'image_image', 1);
  }

}
