<?php

namespace Drupal\item_lot\Tests;

/**
 * Tests item_lot entity.
 *
 * @group item_lot
 */
class ItemLotTest extends ItemLotTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['block', 'views'];

  /**
   * A user with permission to administer item_lot.
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
      'administer item lots',
      'access item lot',
      'edit ' . $this->itemType->id() . ' item',
      'access item',
    ]);
  }

  /**
   * Test list, add, edit.
   */
  public function testList() {
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('local_tasks_block');

    $item_lot_control_enabled = $this->createItem([
      'lot_control' => TRUE,
    ]);
    $item_lot_control_disabled = $this->createItem([
      'lot_control' => FALSE,
    ]);

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/item/' . $item_lot_control_disabled->id());
    $this->assertNoLinkByHref('admin/item/' . $item_lot_control_disabled->id() . '/lot');

    $this->drupalGet('admin/item/' . $item_lot_control_enabled->id());
    $this->assertLinkByHref('admin/item/' . $item_lot_control_enabled->id() . '/lot');

    $this->clickLink(t('Lots'));
    $this->assertResponse(200);
    $this->assertLinkByHref('admin/item/' . $item_lot_control_enabled->id() . '/lot/add');

    $this->clickLink(t('Add lot'));
    $this->assertResponse(200);

    $edit = [
      'title[0][value]' => $this->randomMachineName(8),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);
    $this->assertText($edit['title[0][value]']);
  }

  /**
   * Tests edit page.
   */
  public function testEdit() {
    $this->drupalPlaceBlock('local_tasks_block');

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/item_lot/' . $this->itemLot->id());
    $this->assertResponse(200);
    $this->assertLinkByHref('admin/item_lot/' . $this->itemLot->id() . '/edit');

    $this->clickLink(t('Edit'));
    $this->assertResponse(200);

    $edit = [
      'title[0][value]' => $this->randomMachineName(8),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);
    $this->assertText($edit['title[0][value]']);
  }

}
