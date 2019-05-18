<?php

namespace Drupal\role_expose\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Make sure all grant's are available in <em>admin/config/people</em>.
 *
 * @group Role Expose
 */
class RoleExposePermissionsAvailableTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['role_expose'];

  /**
   * Test that permissions are in place in permission granting page.
   */
  public function testRoleExposeUserPermissionsGrantable() {

    $user_admin = $this->drupalCreateUser(['administer permissions']);
    $this->drupalLogin($user_admin);

    $this->drupalGet('admin/people/permissions');
    $this->drupalGetHeaders(200, t('Make sure user can edit user permissions.'));

    $this->assertText(t('View own exposed roles'), t('"View own exposed roles" -grant available'));

    $warning = t('Warning: Give to trusted roles only; this permission has security implications.');
    $perms_2 = t('View exposed roles for all users');
    $this->assertText($perms_2, t('"View exposed roles for all users" -grant available'));
    $this->assertRaw('<div class="permission"><span class="title">' . $perms_2
        . '</span><div class="description"><em class="permission-warning">'
        . $warning, t('"restrict access" effective with "View exposed roles for all users" -permission'));
  }

}
