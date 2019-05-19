<?php

namespace Drupal\Tests\menu_link\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests menu link field functionality.
 *
 * @group Menu
 */
class MenuLinkFieldStandardTest extends BrowserTestBase {

  /**
   * Test paths in the Standard profile.
   */
  protected $profile = 'standard';

  /**
   * An admin user with all permissions.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $perms = array_keys(\Drupal::service('user.permissions')->getPermissions());
    $this->adminUser = $this->drupalCreateUser($perms);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests field CRUD on the node form and field configurations.
   */
  public function testLinkEdit() {
    // Ensure that the field_menu link got created.
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Foobar',
      'promote' => 1,
      'status' => 1,
    ]);
    $this->drupalGet("node/{$node->id()}/edit");
    $this->assertNoFieldChecked('edit-field-menu-0-enabled');
    $title = $this->randomString();
    $edit = $this->translatePostValues([
      'field_menu' => [
        0 => [
          'enabled' => TRUE,
          'title' => $title,
        ],
      ],
    ]);
    $this->drupalPostForm("node/{$node->id()}/edit", $edit, 'Save and keep published');
    $this->drupalGet("node/{$node->id()}/edit");
    $this->assertFieldChecked('edit-field-menu-0-enabled');
    $this->assertOptionSelected('edit-field-menu-0-menu-parent', 'main:');
    // Enable another menu.
    $edit = $this->translatePostValues([
      'field' => [
        'settings' => [
          'available_menus' => [
            'footer' => TRUE,
            'main' => TRUE,
          ],
        ],
      ],
    ]);
    $this->drupalPostForm('admin/structure/types/manage/article/fields/node.article.field_menu', $edit, 'Save settings');
    $this->drupalGet("node/{$node->id()}/edit");
    $this->assertOptionSelected('edit-field-menu-0-menu-parent', 'main:');
    $edit = $this->translatePostValues([
      'field_menu' => [
        0 => [
          'menu_parent' => 'footer:',
        ],
      ],
    ]);
    $this->drupalPostForm("node/{$node->id()}/edit", $edit, 'Save and keep published');
    $this->drupalGet("node/{$node->id()}/edit");
    $this->assertOptionSelected('edit-field-menu-0-menu-parent', 'footer:');
  }

}
