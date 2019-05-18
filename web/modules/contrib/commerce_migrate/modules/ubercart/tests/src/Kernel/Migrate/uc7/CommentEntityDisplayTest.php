<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc7;

use Drupal\Core\Entity\Entity\EntityViewDisplay;

/**
 * Tests the migration of comment entity displays from Drupal 7.
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc7
 */
class CommentEntityDisplayTest extends Ubercart7TestBase {

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
    $this->executeMigration('uc7_comment_entity_display');
  }

  /**
   * Asserts various aspects of a comment component in an entity view display.
   *
   * @param string $id
   *   The entity ID.
   * @param string $component_id
   *   The ID of the display component.
   */
  protected function assertDisplay($id, $component_id) {
    $component = EntityViewDisplay::load($id)->getComponent($component_id);
    $this->assertInternalType('array', $component);
    $this->assertSame('hidden', $component['label']);
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
