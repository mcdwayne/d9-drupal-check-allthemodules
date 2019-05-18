<?php

namespace Drupal\uom\Tests;

/**
 * Tests UOM module.
 *
 * @group uom
 */
class UomTest extends UomTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['block', 'views'];

  /**
   * A user with permission to administer uom.
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
      'administer uoms',
    ]);
  }

  /**
   * Test list, add, save.
   */
  public function testList() {
    $this->drupalPlaceBlock('local_actions_block');

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/uom');
    $this->assertResponse(200);
    $this->assertLinkByHref('admin/uom/add');

    $this->clickLink(t('Add uom'));
    $this->assertResponse(200);

    $edit = [
      'label' => $this->randomMachineName(8),
      'id' => strtolower($this->randomMachineName(8)),
      'class' => 'quantity',
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

    $this->drupalGet('admin/uom/hour/edit');
    $this->assertResponse(200);
    $edit = [
      'label' => $this->randomMachineName(8),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);
    $this->assertText($edit['label']);
  }

}
