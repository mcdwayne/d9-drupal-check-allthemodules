<?php

/**
 * @file
 * Contains \Drupal\entity_jump_menu\Tests\EntityJumpMenuBlockTest.
 */

namespace Drupal\entity_jump_menu\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the implementation of Entity jump menu block.
 *
 * @group EntityJumpMenu
 */
class EntityJumpMenuBlockTest extends WebTestBase {

  /**
   * An admins user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * A node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['block', 'node', 'user', 'test_page_test', 'entity_jump_menu'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create an administrative user and log it in.
    $this->adminUser = $this->drupalCreateUser(['administer users']);
    $this->drupalLogin($this->adminUser);

    // Create a node.
    $this->drupalCreateContentType(['type' => 'page']);
    $this->node = $this->drupalCreateNode();

    // Place Entity jump menu block.
    $this->drupalPlaceBlock('entity_jump_menu');
  }

  /**
   * Tests that entity jump menu added to block.
   */
  public function testEntityJumpMenuBlock() {
    // Check that no entity id is set.
    $this->drupalGet('test-page');
    $this->assertFieldById('edit-entity-id', '');

    // Check invalid entity type and id.
    $this->drupalPostForm('test-page', ['entity_type' => 'node', 'entity_id' => '999'], t('Go'));
    $this->assertRaw('<input data-drupal-selector="edit-entity-id" type="text" id="edit-entity-id" name="entity_id" value="999" size="6" maxlength="10" class="form-text required error" required="required" aria-required="true" aria-invalid="true" />');
    // $this->assertRaw('There are no entities matching "<em class="placeholder">node</em>:<em class="placeholder">999</em>".');

    // Check jump to a node.
    $this->drupalPostForm('test-page', ['entity_type' => 'node', 'entity_id' => '1'], t('Go'));
    // $this->assertNoRaw('There are no entities matching "<em class="placeholder">node</em>:<em class="placeholder">1</em>".');
    $this->assertRaw('<title>' . $this->node->getTitle() . ' | Drupal</title>');

    // Check that node entity type and id is set.
    $this->drupalGet('node/' . $this->node->id());
    $this->assertOptionSelected('edit-entity-type', 'node');
    $this->assertFieldById('edit-entity-id', $this->node->id());

    // Check jump to a user.
    $this->drupalPostForm('node/' . $this->node->id(), ['entity_type' => 'user', 'entity_id' => $this->adminUser->id()], t('Go'));
    $this->assertRaw('<title>' . $this->adminUser->label() . ' | Drupal</title>');

    // Check that user entity type and id is set.
    $this->drupalGet('user/' . $this->adminUser->id());
    $this->assertOptionSelected('edit-entity-type', 'user');
    $this->assertFieldById('edit-entity-id', $this->adminUser->id());
  }

}
