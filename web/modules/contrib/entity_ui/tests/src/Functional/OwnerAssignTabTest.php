<?php

namespace Drupal\Tests\entity_ui\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the owner assign content plugin
 *
 * @group entity_ui
 */
class OwnerAssignTabTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  public static $modules = [
    'block',
    'node',
    'field_ui',
    'entity_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->nodeStorage = $this->container->get('entity_type.manager')
      ->getStorage('node');
    $this->entityTabStorage = $this->container->get('entity_type.manager')
      ->getStorage('entity_tab');

    // Create an Article node type.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    }

    $this->drupalPlaceBlock('page_title_block');
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('local_tasks_block');
  }

  /**
   * Tests the owner assign content plugin.
   */
  public function testNodeChangeOwner() {
    // Create an entity tab showing the 'owner_assign' plugin.
    $tab_values = [
      'id' => 'node.' . strtolower($this->randomMachineName()),
      'target_entity_type' => 'node',
      'label' => $this->randomString(),
      'tab_title' => $this->randomString(),
      'page_title' => $this->randomString(),
      'path' => $this->randomMachineName(),
      'target_bundles[article]' => 0,
      'content_plugin' => 'owner_assign',
      'content_config' => [],
    ];
    $entity_tab = $this->entityTabStorage->create($tab_values);
    $entity_tab->save();

    // Rebuild the routes.
    // TODO: this should be getting handled by the entity's postSave(), but
    // isn't.
    \Drupal::service('router.builder')->rebuild();

    // Log in as a user who can access the tab.
    $this->drupalLogin($this->drupalCreateUser([
      "access {$tab_values['path']} tab on any node article entities",
    ]));

    // Create users to assign as owners of the node.
    $original_owner = $this->drupalCreateUser();
    $new_owner = $this->drupalCreateUser();

    // Create an article node.
    $node_values = [
      'type' => 'article',
      'uid' => $original_owner->id(),
      'title' => $this->randomMachineName(),
      'status' => TRUE,
    ];
    $node = $this->nodeStorage->create($node_values);
    $node->save();

    // Test the node tab displays correctly.
    $this->drupalGet('node/' . $node->id() . '/' . $tab_values['path']);
    $this->assertSession()->statusCodeEquals(200);

    $edit = [
      'owner_uid' => $new_owner->getUsername() . ' (' . $new_owner->id() . ')',
    ];
    $this->drupalPostForm(NULL, $edit, 'Change owner');

    // Reload the node.
    $node = $this->nodeStorage->load($node->id());
    $this->assertEquals($node->uid->target_id, $new_owner->id(), "The owner of the node was changed.");
  }

}
