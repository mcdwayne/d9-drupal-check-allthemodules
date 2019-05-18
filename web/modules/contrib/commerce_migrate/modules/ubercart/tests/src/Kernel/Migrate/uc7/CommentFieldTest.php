<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc7;

use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests the migration of product comment fields from Ubercart 7.
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc7
 */
class CommentFieldTest extends Ubercart7TestBase {

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
    $this->installEntitySchema('commerce_product');
    $this->installConfig(['comment', 'commerce_product']);
    $this->executeMigrations([
      'uc7_comment_type',
      'uc7_product_type',
      'uc7_comment_field',
    ]);
  }

  /**
   * Asserts a comment field entity.
   *
   * @param string $comment_type
   *   The comment type.
   */
  protected function assertEntity($comment_type) {
    $entity = FieldStorageConfig::load('commerce_product.' . $comment_type);
    $this->assertInstanceOf(FieldStorageConfig::class, $entity);
    $this->assertSame('commerce_product', $entity->getTargetEntityTypeId());
    $this->assertSame('comment', $entity->getType());
    $this->assertSame($comment_type, $entity->getSetting('comment_type'));
  }

  /**
   * Tests the migrated comment fields.
   */
  public function testMigration() {
    $this->assertEntity('comment_node_product');
    $this->assertEntity('comment_node_entertainment');
  }

}
