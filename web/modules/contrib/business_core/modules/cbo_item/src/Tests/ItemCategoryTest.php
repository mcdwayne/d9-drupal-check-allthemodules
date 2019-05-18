<?php

namespace Drupal\cbo_item\Tests;

/**
 * Tests item category entity.
 *
 * @group cbo_item
 */
class ItemCategoryTest extends ItemTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['block', 'views'];

  /**
   * A user with permission to administer item categories.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'administer item categories',
    ]);
  }

  /**
   * Tests the list, add, save.
   */
  public function testList() {
    $this->drupalPlaceBlock('local_actions_block');

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/item/category');
    $this->assertResponse(200);
    $this->assertLinkByHref('admin/item/category/' . $this->itemCategory->id() . '/item');
    $this->assertLinkByHref('admin/item/category/add');

    $this->clickLink(t('Add item category'));
    $this->assertResponse(200);

    $edit = [
      'label' => $this->randomMachineName(8),
      'id' => strtolower($this->randomMachineName(8)),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);
    $this->assertText($edit['label']);
  }

  /**
   * Tests the edit page.
   */
  public function testEdit() {
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/item/category/' . $this->itemCategory->id() . '/edit');
    $this->assertResponse(200);

    $edit = [
      'label' => $this->randomMachineName(8),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);
    $this->assertText($edit['label']);
  }

}
