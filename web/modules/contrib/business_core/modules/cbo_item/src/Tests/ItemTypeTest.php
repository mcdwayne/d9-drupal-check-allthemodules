<?php

namespace Drupal\cbo_item\Tests;

/**
 * Tests item type entity.
 *
 * @group cbo_item
 */
class ItemTypeTest extends ItemTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['block', 'views'];

  /**
   * A user with permission to administer item types.
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
      'administer item types',
    ]);
  }

  /**
   * Tests item type list, add, save.
   */
  public function testItemTypePage() {
    $this->drupalPlaceBlock('local_actions_block');

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/item/type');
    $this->assertResponse(200);
    $this->assertLinkByHref('admin/item/type/add');

    $this->clickLink(t('Add item type'));
    $this->assertResponse(200);

    $edit = [
      'label' => $this->randomMachineName(8),
      'id' => strtolower($this->randomMachineName(8)),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);
    $this->assertText($edit['label']);
  }

}
