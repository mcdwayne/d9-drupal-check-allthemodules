<?php

namespace Drupal\quick_code\Tests;

/**
 * Tests quick_code_type entity.
 *
 * @group quick_code
 */
class QuickCodeTypeTest extends QuickCodeTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['block', 'views'];

  /**
   * A user with permission to administer quick code.
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
      'administer quick codes',
      'access quick code',
    ]);
  }

  /**
   * Tests the list, add, save.
   */
  public function testList() {
    $this->drupalPlaceBlock('local_actions_block');

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/quick_code_type');
    $this->assertResponse(200);
    $this->assertLink($this->quickCodeType->label());
    $this->assertLinkByHref('admin/quick_code_type/add');

    $this->clickLink(t('Add quick code type'));
    $this->assertResponse(200);

    $edit = [
      'id' => strtolower($this->randomMachineName(8)),
      'label' => $this->randomMachineName(8),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);
    $this->assertText($edit['label']);
  }

  /**
   * Tests the edit form.
   */
  public function testEdit() {
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/quick_code_type/' . $this->quickCodeType->id() . '/edit');
    $this->assertResponse(200);

    $edit = [
      'label' => $this->randomMachineName(8),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);
    $this->assertText($edit['label']);
  }

}
