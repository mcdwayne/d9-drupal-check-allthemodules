<?php

namespace Drupal\cbo_inventory\Tests;

/**
 * Tests subinventory type entity.
 *
 * @group cbo_inventory
 */
class SubinventoryTypeTest extends InventoryTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['block', 'views'];

  /**
   * A user with permission to administer subinventory types.
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
      'administer subinventory types',
    ]);
  }

  /**
   * Tests subinventory type list, add, save.
   */
  public function testSubinventoryTypePage() {
    $this->drupalPlaceBlock('local_actions_block');

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/subinventory/type');
    $this->assertResponse(200);
    $this->assertLinkByHref('admin/subinventory/type/add');

    $this->clickLink(t('Add subinventory type'));
    $this->assertResponse(200);
    $edit = [
      'id' => strtolower($this->randomMachineName(8)),
      'label' => $this->randomMachineName(8),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);
    $this->assertText($edit['label']);
  }

}
