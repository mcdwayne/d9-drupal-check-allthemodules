<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc7;

use Drupal\field\Entity\FieldConfig;

/**
 * Tests the migration of comment field instances from Drupal 7.
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc7
 */
class CommentFieldInstanceTest extends Ubercart7TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'comment',
    'commerce_price',
    'commerce_product',
    'commerce_store',
    'node',
    'path',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->migrateContentTypes();
    $this->migrateCommentFields();
  }

  /**
   * Asserts a comment field instance entity.
   *
   * @param string $bundle
   *   The bundle ID.
   * @param string $field_name
   *   The field name.
   * @param int $default_value
   *   The field's default_value setting.
   * @param int $default_mode
   *   The field's default_mode setting.
   * @param int $per_page
   *   The field's per_page setting.
   * @param bool $anonymous
   *   The field's anonymous setting.
   * @param int $form_location
   *   The field's form_location setting.
   * @param bool $preview
   *   The field's preview setting.
   */
  protected function assertEntity($bundle, $field_name, $default_value, $default_mode, $per_page, $anonymous, $form_location, $preview) {
    $entity = FieldConfig::load("commerce_product.$bundle.$field_name");
    $this->assertInstanceOf(FieldConfig::class, $entity);
    $this->assertSame('commerce_product', $entity->getTargetEntityTypeId());
    $this->assertSame('Comments', $entity->label());
    $this->assertTrue($entity->isRequired());
    $this->assertSame($bundle, $entity->getTargetBundle());
    $this->assertSame($field_name, $entity->getFieldStorageDefinition()->getName());
    $this->assertSame($default_value, $entity->get('default_value')[0]['status']);
    $this->assertSame($default_mode, $entity->getSetting('default_mode'));
    $this->assertSame($per_page, $entity->getSetting('per_page'));
    $this->assertSame($anonymous, $entity->getSetting('anonymous'));
    $this->assertSame($form_location, $entity->getSetting('form_location'));
    $this->assertSame($preview, $entity->getSetting('preview'));
  }

  /**
   * Tests the migrated fields.
   */
  public function testMigration() {
    $this->assertEntity('product', 'comment_node_product', 2, 1, 50, 0, FALSE, 1);
    $this->assertEntity('entertainment', 'comment_node_entertainment', 2, 1, 50, 0, FALSE, 1);
  }

}
