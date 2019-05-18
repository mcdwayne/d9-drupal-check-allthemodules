<?php

namespace Drupal\supplier\Tests;

/**
 * Tests supplier type entity.
 *
 * @group supplier
 */
class SupplierTypeTest extends SupplierTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['block', 'views'];

  /**
   * A user with permission to administer supplier types.
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
      'access supplier overview',
      'administer supplier types',
    ]);
  }

  /**
   * Tests supplier type list, add, save.
   */
  public function testSupplierTypePage() {
    $this->drupalPlaceBlock('local_actions_block');

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/supplier/type');
    $this->assertResponse(200);
    $this->assertLinkByHref('admin/supplier/type/add');

    $this->clickLink(t('Add supplier type'));
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
