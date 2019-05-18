<?php

namespace Drupal\Tests\permission_ui\Functional;

/**
 * Test Permission UI functionality.
 *
 * @group permission_ui
 */
class PermissionUiAdminTest extends PermissionUiTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test tab label changes.
   */
  public function testLocalTask() {
    $this->drupalGet('/admin/people');
    $this->assertSession()->linkExists('Users');
    $this->assertSession()->linkNotExists('List');
    $this->assertSession()->linkExists('Permissions');
    $this->assertSession()->linkExists('Roles');
    $this->assertSession()->linkExists('Permissions + Roles');
  }

  /**
   * Test action buttons introduced by the module.
   */
  public function testLocalAction() {
    $this->drupalGet('/admin/people/permission_ui');
    $this->assertSession()->linkExists('Add permission');

    $this->drupalGet('/admin/people/permissions');
    $this->assertSession()->linkExists('Add permission');
    $this->assertSession()->linkExists('Add role');
  }

  /**
   * Test tab label changes.
   */
  public function testPermissionsCreatePage() {
    $this->drupalGet('/admin/people/permission_ui/add');
    $this->assertSession()->fieldExists('entity_type');
    $this->assertSession()->fieldExists('bundle_type');
    $this->assertSession()->fieldExists('operation');
    $this->assertSession()->fieldExists('scope');
    $this->assertSession()->fieldExists('is_restricted');
    $this->assertSession()->fieldExists('description');
  }

}
