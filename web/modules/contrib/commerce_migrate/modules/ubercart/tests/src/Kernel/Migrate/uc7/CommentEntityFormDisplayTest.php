<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc7;

use Drupal\Core\Entity\Entity\EntityFormDisplay;

/**
 * Tests the migration of comment form display from Drupal 7.
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc7
 */
class CommentEntityFormDisplayTest extends Ubercart7TestBase {

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
    $this->executeMigration('uc7_comment_entity_form_display');
  }

  /**
   * Asserts various aspects of a comment component in an entity form display.
   *
   * @param string $id
   *   The entity ID.
   * @param string $component_id
   *   The ID of the form component.
   */
  protected function assertDisplay($id, $component_id) {
    $component = EntityFormDisplay::load($id)->getComponent($component_id);
    $this->assertInternalType('array', $component);
    $this->assertSame('comment_default', $component['type']);
    $this->assertSame(20, $component['weight']);
  }

  /**
   * Tests the migrated display configuration.
   */
  public function testMigration() {
    $this->assertDisplay('commerce_product.product.default', 'comment_node_product');
    $this->assertDisplay('commerce_product.entertainment.default', 'comment_node_entertainment');
  }

}
