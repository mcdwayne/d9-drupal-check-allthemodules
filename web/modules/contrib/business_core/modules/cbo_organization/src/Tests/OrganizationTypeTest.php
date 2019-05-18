<?php

namespace Drupal\cbo_organization\Tests;

/**
 * Tests organization type entity.
 *
 * @group cbo_organization
 */
class OrganizationTypeTest extends OrganizationTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['block', 'views'];

  /**
   * A user with permission to administer organization types.
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
      'administer organization types',
      'access organization',
    ]);
  }

  /**
   * Tests the list, add, save.
   */
  public function testList() {
    $this->drupalPlaceBlock('local_actions_block');

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/organization/type');
    $this->assertResponse(200);
    $this->assertLinkByHref('admin/organization/type/add');

    $this->clickLink(t('Add organization type'));
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
   * Tests the edit form.
   */
  public function testEdit() {
    $this->drupalPlaceBlock('local_tasks_block');

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/organization/type/company');
    $this->assertResponse(200);

    $edit = [
      'label' => $this->randomMachineName(8),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);
    $this->assertText($edit['label']);
  }

}
