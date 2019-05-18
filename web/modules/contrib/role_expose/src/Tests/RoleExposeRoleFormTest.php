<?php

namespace Drupal\role_expose\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Make sure content exists on logged in user profile page.
 *
 * @group Role Expose
 */
class RoleExposeRoleFormTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['role_expose'];

  /**
   * Make sure all user roles are listed in config page.
   */
  protected function testUserRoleForm() {

    $user_admin = $this->drupalCreateUser(['administer permissions']);
    $this->drupalLogin($user_admin);

    $this->drupalGet('admin/people/roles/add');

    // Check administrator -role, by form element name.
    $this->assertText(t('Role expose'), t('Selector label exists.'));
    $this->assertOption('edit-role-expose', '0', t('Select option "Never" is present.'));
    $this->assertOption('edit-role-expose', '1', t('Select option "User with this role" is present.'));
    $this->assertOption('edit-role-expose', '2', t('Select option "User without this role" is present.'));
    $this->assertOption('edit-role-expose', '3', t('Select option "Always" is present.'));
    $this->assertText(t('Choose when this role should displayed in User profile page.'), t('Selector Description text found.'));
  }

}
