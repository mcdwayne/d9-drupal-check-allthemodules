<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc7;

use Drupal\comment\Entity\CommentType;

/**
 * Tests the migration of product comment types from Ubercart 7.
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc7
 */
class CommentTypeTest extends Ubercart7TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'comment', 'text'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('comment');
    $this->installConfig(['comment']);
    $this->executeMigration('uc7_comment_type');
  }

  /**
   * Asserts a comment type entity.
   *
   * @param string $id
   *   The entity ID.
   * @param string $label
   *   The entity label.
   */
  protected function assertEntity($id, $label) {
    $entity = CommentType::load($id);
    $this->assertInstanceOf(CommentType::class, $entity);
    $this->assertSame($label, $entity->label());
    $this->assertSame('commerce_product', $entity->getTargetEntityTypeId());
  }

  /**
   * Tests the migrated comment types.
   */
  public function testMigration() {
    $this->assertEntity('comment_node_product', 'Product comment');
    $this->assertEntity('comment_node_entertainment', 'Entertainment comment');
  }

}
