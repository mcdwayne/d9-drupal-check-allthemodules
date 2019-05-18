<?php

namespace Drupal\cbo_inventory\Tests;

/**
 * Tests subinventory entity.
 *
 * @group cbo_inventory
 */
class SubinventoryTest extends InventoryTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['block', 'views'];

  /**
   * A user with permission to administer subinventory.
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
      'administer subinventories',
      'access subinventory',
      'access organization',
    ]);
    $this->adminUser->people->target_id = $this->people->id();
    $this->adminUser->save();
  }

  /**
   * Test list, add, save.
   */
  public function testList() {
    $this->drupalPlaceBlock('local_actions_block');

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/subinventory');
    $this->assertResponse(200);
    $this->assertLinkByHref('admin/subinventory/add');

    $this->clickLink(t('Add subinventory'));
    $this->assertResponse(200);
    $this->assertLinkByHref('admin/subinventory/add/storage');

    $this->clickLink(t('Storage'));
    $this->assertResponse(200);
    $edit = [
      'title[0][value]' => $this->randomMachineName(8),
      'description[0][value]' => $this->randomMachineName(8),
      'organization[0][target_id]' => $this->organization->label() . ' (' . $this->organization->id() . ')',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);
    $this->assertText($edit['title[0][value]']);
  }

  /**
   * Tests edit page.
   */
  public function testEdit() {
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/subinventory/' . $this->subinventory->id() . '/edit');
    $this->assertResponse(200);

    $edit = [
      'title[0][value]' => $this->randomMachineName(8),
      'description[0][value]' => $this->randomMachineName(8),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);
    $this->assertText($edit['title[0][value]']);
  }

}
