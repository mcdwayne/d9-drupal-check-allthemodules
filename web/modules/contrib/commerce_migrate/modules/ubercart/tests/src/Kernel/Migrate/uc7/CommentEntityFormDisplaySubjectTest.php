<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc7;

use Drupal\Core\Entity\Entity\EntityFormDisplay;

/**
 * Tests the migration of comment form's subject display from Drupal 7.
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc7
 */
class CommentEntityFormDisplaySubjectTest extends Ubercart7TestBase {

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
    $this->installConfig(['comment', 'commerce_product', 'node']);
    $this->executeMigrations([
      'uc7_comment_type',
      'uc7_comment_entity_form_display_subject',
    ]);
  }

  /**
   * Asserts that the comment subject field is visible for a node type.
   *
   * @param string $id
   *   The entity form display ID.
   */
  protected function assertSubjectVisible($id) {
    $component = EntityFormDisplay::load($id)->getComponent('subject');
    $this->assertInternalType('array', $component);
    $this->assertSame('string_textfield', $component['type']);
    $this->assertSame(10, $component['weight']);
  }

  /**
   * Asserts that the comment subject field is not visible for a node type.
   *
   * @param string $id
   *   The entity form display ID.
   */
  protected function assertSubjectNotVisible($id) {
    $component = EntityFormDisplay::load($id)->getComponent('subject');
    $this->assertNull($component);
  }

  /**
   * Tests the migrated display configuration.
   */
  public function testMigration() {
    $this->assertSubjectVisible('comment.comment_node_product.default');
    $this->assertSubjectVisible('comment.comment_node_entertainment.default');
  }

}
