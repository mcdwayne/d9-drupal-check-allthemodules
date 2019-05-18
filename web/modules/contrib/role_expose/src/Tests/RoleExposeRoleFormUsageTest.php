<?php

namespace Drupal\role_expose\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\role_expose\ExposableRolesInterface;

/**
 * Make sure content exists on logged in user profile page.
 *
 * @group Role Expose
 */
class RoleExposeRoleFormUsageTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['role_expose'];

  /**
   * Make sure all user roles are listed in config page.
   */
  protected function testUserRoleFormUsage() {

    // More privileged role:
    $user_admin = $this->createUser([], 'admin.user', TRUE);
    $this->drupalLogin($user_admin);

    // Set visibilities for test roles.
    $role_hidden = $this->createRole([], 'hidden', 'Role never visible');
    $this->drupalGet('admin/people/roles/manage/' . $role_hidden);
    $edit['role_expose'] = ExposableRolesInterface::EXPOSE_NEVER;
    $this->drupalPostForm(NULL, $edit, 'Save');

    $role_visible_when_has = $this->createRole([], 'visible_when_has', 'Role visible when has');
    $this->drupalGet('admin/people/roles/manage/' . $role_visible_when_has);
    $edit['role_expose'] = ExposableRolesInterface::EXPOSE_WITH;
    $this->drupalPostForm(NULL, $edit, 'Save');

    $role_visible_when_not_has = $this->createRole([], 'visible_when_not_has', 'Role visible when not has');
    $this->drupalGet('admin/people/roles/manage/' . $role_visible_when_not_has);
    $edit['role_expose'] = ExposableRolesInterface::EXPOSE_WITHOUT;
    $this->drupalPostForm(NULL, $edit, 'Save');

    $role_visible = $this->createRole([], 'visible', 'Role always visible');
    $this->drupalGet('admin/people/roles/manage/' . $role_visible);
    $edit['role_expose'] = ExposableRolesInterface::EXPOSE_ALWAYS;
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Log the resulted Permissions -page (incl. roles and grants)
    $this->drupalGet('admin/people/permissions');

    // Grant some test roles to the basic account.
    $user_basic = $this->createUser([], 'basic.user');
    $this->drupalGet('user/' . $user_basic->id() . '/edit');
    $edit = [
      'roles[visible]' => TRUE,
      'roles[hidden]' => TRUE,
      'roles[visible_when_has]' => TRUE,
      'roles[visible_when_not_has]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertText(t('The changes have been saved.'), t('Roles saved'));

    // Check that we have NO roles visible in User profile page (no grants yet!)
    $this->drupalLogin($user_basic);
    $this->drupalGet('user');
    $this->assertNoText(t('Roles'), t('Role Expose -section is hidden (title)'));
    $this->assertNoText(t('The roles give different permissions on the site. Please contact your site administration for more info.'), t('Role Expose -section is hidden (content check)'));
    $this->assertNoText(t('Role always visible'), t('Role <em>Role always visible</em> is NOT printed in the UI'));
    $this->assertNoText(t('Role never visible'), t('Role <em>Role never visible</em> is NOT printed in the UI'));
    $this->assertNoText(t('Role visible when has'), t('Role <em>Role visible when has</em> is NOT printed in the UI'));
    $this->assertNoText(t('Role visible when not has'), t('Role <em>Role visible when not has</em> is NOT printed in the UI'));

    $this->drupalLogin($user_admin);

    $perms = [
      'view own roles',
    ];
    // Grant some test roles to the priviledged account.
    $user_more_priviledged = $this->createUser($perms, 'priviledged.user');
    $this->drupalGet('user/' . $user_more_priviledged->id() . '/edit');
    $edit = [
      'roles[visible]' => TRUE,
      'roles[hidden]' => TRUE,
      'roles[visible_when_has]' => TRUE,
      'roles[visible_when_not_has]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertText(t('The changes have been saved.'), t('Roles saved'));

    // Check that we have roles in User profile page -  grants are now set!
    $this->drupalLogin($user_more_priviledged);
    $this->drupalGet('user');
    $this->assertText(t('Roles'), t('Role Expose -section is visible (title)'));
    $this->assertText(t('The roles give different permissions on the site. Please contact your site administration for more info.'), t('Role Expose -section visible (content check)'));
    $this->assertText(t('Role always visible'), t('Role <em>Role always visible</em> is printed in the UI'));
    $this->assertNoText(t('Role never visible'), t('Role <em>Role never visible</em> is NOT printed in the UI'));
    $this->assertText(t('Role visible when has'), t('Role <em>Role visible when has</em> is printed in the UI'));
    $this->assertNoText(t('Role visible when not has'), t('Role <em>Role visible when not has</em> is NOT printed in the UI'));

    // Check that we have roles in User profile page -  grants are now set!
    $perms = [
      'access user profiles',
      'view roles of all users',
    ];
    $user_see_other_users_roles = $this->createUser($perms, 'see_all_user_roles.user');
    $this->drupalLogin($user_see_other_users_roles);
    // Go to see *other* user's profile, with 'view roles of all users' -perms.
    $this->drupalGet('user/' . $user_more_priviledged->id());
    $this->assertText(t('Roles'), t('Role Expose -section is visible (title)'));
    $this->assertText(t('Role always visible'), t('Role <em>Role always visible</em> is printed in the UI'));
    $this->assertNoText(t('Role never visible'), t('Role <em>Role never visible</em> is NOT printed in the UI'));
    $this->assertText(t('Role visible when has'), t('Role <em>Role visible when has</em> is printed in the UI'));
    $this->assertNoText(t('Role visible when not has'), t('Role <em>Role visible when not has</em> is NOT printed in the UI'));

    // Check that we have roles in User profile page -  grants are now set!
    $perms = [
      'access user profiles',
    ];
    $user_see_other_users_profile = $this->createUser($perms, 'see_other_user_profiles.user');
    $this->drupalLogin($user_see_other_users_profile);
    // Go to see *other* user's profile, with 'view roles of all users' -perms.
    $this->drupalGet('user/' . $user_more_priviledged->id());
    $this->assertNoText(t('Roles'), t('Role Expose -section is visible (title)'));
    $this->assertNoText(t('Role always visible'), t('Role <em>Role always visible</em> is printed in the UI'));

    $this->drupalLogin($user_admin);
    $this->drupalGet('user/' . $user_more_priviledged->id() . '/edit');
    $edit = [
      'roles[visible]' => FALSE,
      'roles[hidden]' => FALSE,
      'roles[visible_when_has]' => FALSE,
      'roles[visible_when_not_has]' => FALSE,
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertText(t('The changes have been saved.'), t('Roles saved'));

    // Check that we have roles in User profile page -  grants are now set!
    $this->drupalLogin($user_more_priviledged);
    $this->drupalGet('user');
    $this->assertText(t('Roles'), t('Role Expose -section is visible (title)'));
    $this->assertText(t('The roles give different permissions on the site. Please contact your site administration for more info.'), t('Role Expose -section visible (content check)'));
    $this->assertText(t('Role always visible'), t('Role <em>Role always visible</em> is printed in the UI'));
    $this->assertNoText(t('Role never visible'), t('Role <em>Role never visible</em> is NOT printed in the UI'));
    $this->assertNOText(t('Role visible when has'), t('Role <em>Role visible when has</em> is printed in the UI'));
    $this->assertText(t('Role visible when not has'), t('Role <em>Role visible when not has</em> is NOT printed in the UI'));

  }

}
