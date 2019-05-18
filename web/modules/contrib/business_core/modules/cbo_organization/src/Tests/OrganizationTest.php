<?php

namespace Drupal\cbo_organization\Tests;

/**
 * Tests organization entity.
 *
 * @group cbo_organization
 */
class OrganizationTest extends OrganizationTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['block', 'views'];

  /**
   * A user with permission to administer organization.
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
      'administer organizations',
      'access organization',
    ]);
  }

  /**
   * Tests the list, add, save.
   */
  public function testList() {
    $this->drupalPlaceBlock('local_actions_block');

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/organization');
    $this->assertResponse(200);
    $this->assertLinkByHref('admin/organization/add');

    $this->clickLink(t('Add organization'));
    $this->assertResponse(200);
    $this->assertLinkByHref('admin/organization/add/company');

    $this->clickLink(t('Company'));
    $this->assertResponse(200);

    $edit = [
      'title[0][value]' => $this->randomMachineName(8),
      'description[0][value]' => $this->randomMachineName(8),
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

    $this->drupalGet('admin/organization/' . $this->organization->id());
    $this->assertResponse(200);
    $this->assertLinkByHref('admin/organization/' . $this->organization->id() . '/edit');

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
