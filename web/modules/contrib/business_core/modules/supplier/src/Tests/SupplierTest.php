<?php

namespace Drupal\supplier\Tests;

/**
 * Tests supplier entity.
 *
 * @group supplier
 */
class SupplierTest extends SupplierTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['block', 'views'];

  /**
   * A user with permission to administer supplier.
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
      'administer suppliers',
      'access supplier',
    ]);
  }

  /**
   * Test list, add, save.
   */
  public function testList() {
    $this->drupalPlaceBlock('local_actions_block');

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/supplier');
    $this->assertResponse(200);
    $this->assertLinkByHref('admin/supplier/add');

    $this->clickLink(t('Add supplier'));
    $this->assertResponse(200);
    $this->assertLinkByHref('admin/supplier/add/' . $this->supplierType->id());

    $this->clickLink($this->supplierType->label());
    $this->assertResponse(200);

    $edit = [
      'title[0][value]' => $this->randomMachineName(8),
      'number[0][value]' => $this->randomMachineName(8),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);
    $this->assertText($edit['title[0][value]']);
  }

  /**
   * Tests the edit page.
   */
  public function testEdit() {
    $this->drupalPlaceBlock('local_tasks_block');

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/supplier/' . $this->supplier->id());
    $this->assertLinkByHref('admin/supplier/' . $this->supplier->id() . '/edit');

    $this->clickLink(t('Edit'));
    $this->assertResponse(200);
    $edit = [
      'title[0][value]' => $this->randomMachineName(8),
      'number[0][value]' => $this->randomMachineName(8),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);
    $this->assertText($edit['title[0][value]']);
  }

}
