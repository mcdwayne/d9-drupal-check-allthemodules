<?php

namespace Drupal\bom\Tests;

/**
 * Tests bom entities.
 *
 * @group bom
 */
class BomTest extends BomTestBase {

  /**
   * A user with project admin permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['block', 'views'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'administer boms',
      'access bom',
    ]);
  }

  /**
   * Tests the list, add, save.
   */
  public function testList() {
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/bom');
    $this->assertResponse(200);
    $this->assertLinkByHref('admin/bom/add');

    $this->clickLink(t('Add bom'));
    $this->assertResponse(200);

    $edit = [
      'title[0][value]' => $this->randomMachineName(8),
      'number[0][value]' => $this->randomMachineName(8),
      'item[0][target_id]' => $this->item->label() . ' (' . $this->item->id() . ')',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);
    $this->assertText($edit['title[0][value]']);
  }

  /**
   * Tests the edit form.
   */
  public function testEdit() {
    $this->drupalPlaceBlock('local_tasks_block');

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/bom/' . $this->bom->id());
    $this->assertResponse(200);
    $this->assertLinkByHref('admin/bom/' . $this->bom->id() . '/edit');

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
