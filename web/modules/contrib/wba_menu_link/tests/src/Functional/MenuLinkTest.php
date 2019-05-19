<?php

namespace Drupal\Tests\wba_menu_link\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\workbench_access\Functional\WorkbenchAccessTestTrait;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\system\Entity\Menu;
use Drupal\node\Entity\Node;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Menu\MenuTreeParameters;

/**
 * Tests for Workbench Access Menu Link.
 *
 * @group wba_menu_link
 */
class MenuLinkTest extends BrowserTestBase {

  use WorkbenchAccessTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'menu_link',
    'workbench_access',
    'node',
    'options',
    'user',
    'system',
    'wba_menu_link',
  ];

  /**
   * Test menu link field-based access control for editing nodes.
   */
  public function testAccessControl() {
    // Login the admin user.
    $this->drupalLogin($this->createUser([], NULL, TRUE));

    // Set up a content type, taxonomy field, and taxonomy scheme.
    $this->setUpContentType();

    // Add a menu link field to the content type.
    FieldStorageConfig::create([
      'field_name' => 'field_menu_link',
      'entity_type' => 'node',
      'type' => 'menu_link',
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_menu_link',
      'entity_type' => 'node',
      'bundle' => 'page',
    ])->save();

    $menu = Menu::create([
      'id' => 'test_menu',
      'label' => 'Test menu',
      'description' => 'Description text',
    ]);
    $menu->save();

    // Configure WBA.
    $assert = $this->assertSession();
    $this->drupalGet('admin/config/workflow/workbench_access');
    $assert->statusCodeEquals(200);

    // Check the scheme exists.
    $page = $this->getSession()->getPage();
    $menu_field = $page->find('css', 'input[name=scheme][value=menu_link]');
    $this->assertNotNull($menu_field);
    $page->fillField('scheme', $menu_field->getAttribute('value'));
    $page->checkField('edit-menu-link-test-menu');
    $this->submitForm([], 'Set active scheme');

    $this->drupalGet('admin/config/workflow/workbench_access');
    $assert->statusCodeEquals(200);
    $page->checkField('workbench_access_status_page');
    $page->fillField('field_page', 'field_menu_link');
    $this->submitForm([], 'Save configuration');
    $assert->statusCodeEquals(200);

    $scheme = $this->container->get('plugin.manager.workbench_access.scheme')->getActiveScheme();
    $this->assertEquals('menu_link', $scheme->id());

    // Create an editor role role.
    $editor_rid = $this->createRole([
      'access administration pages',
      'create page content',
      'edit any page content',
      'delete any page content',
      'use workbench access',
    ], 'editor');

    $editor = $this->createUserWithRole($editor_rid);

    // Create three nodes with menu entries.
    // 1. Node with edit access.
    $node1 = Node::create([
      'type' => 'page',
      'title' => 'Node with edit access',
      'field_menu_link' => [
        'menu_name' => 'test_menu',
        'title' => 'Link to node with edit access',
        'description' => 'Test',
      ],
    ]);
    $node1->save();

    /** @var \Drupal\Core\Menu\MenuLinkTree $menu_tree */
    $menu_tree = \Drupal::service('menu.link_tree');
    $parameters = new MenuTreeParameters();
    $parameters->addCondition('title', 'Link to node with edit access');
    $result = $menu_tree->load('test_menu', $parameters);
    $menu_link = reset($result);

    // 2. Child of node with edit access.
    $node2 = Node::create([
      'type' => 'page',
      'title' => 'Child of node with edit access',
      'field_menu_link' => [
        'menu_name' => 'test_menu',
        'parent' => $menu_link->link->getPluginId(),
        'title' => 'Link to child of node with edit access',
        'description' => 'Test',
      ],
    ]);
    $node2->save();

    // 3. Node without edit access.
    $node3 = Node::create([
      'type' => 'page',
      'title' => 'Node without edit access',
      'field_menu_link' => [
        'menu_name' => 'test_menu',
        'title' => 'Link to node without edit access',
        'description' => 'Test',
      ],
    ]);
    $node3->save();

    // Give the editor user access to the right section.
    $this->drupalGet('admin/config/workflow/workbench_access/sections');
    $assert->statusCodeEquals(200);
    $this->clickLink('0 editors', 1);
    $page = $this->getSession()->getPage();
    // We assume the editor is the second to be listed in the user
    // overview (admin is first).
    $page->checkField('editors[' . $editor->id() . ']');
    $this->submitForm([], 'Submit');

    // Log in as the editor.
    $this->drupalLogin($editor);

    // Ensure editor user can edit the nodes with edit access.
    $this->drupalGet('node/' . $node1->id() . '/edit');
    $assert->statusCodeEquals(200);

    $this->drupalGet('node/' . $node2->id() . '/edit');
    $assert->statusCodeEquals(200);

    // Ensure editor cannot edit nodes without edit access.
    $this->drupalGet('node/' . $node3->id() . '/edit');
    $assert->statusCodeEquals(403);
  }

}
