<?php

namespace Drupal\pubkey_encrypt\Tests;

use Drupal\user\Entity\Role;

/**
 * Tests the management of Role keys.
 *
 * @group pubkey_encrypt
 */
class RoleKeysManagement extends PubkeyEncryptTestBase {

  /**
   * Key Repository service.
   *
   * @var \Drupal\key\KeyRepository
   */
  protected $keyRepository;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->keyRepository = \Drupal::service('key.repository');
  }

  /**
   * Tests the creation and deletion of Role keys.
   */
  public function testRoleKeys() {
    // Create a new role.
    $new_role_id = $this->drupalCreateRole(array());

    // Test that a Role key has been created.
    $new_role_key = $this->keyRepository->getKey($new_role_id . "_role_key");
    $this->assertNotNull($new_role_key, "Role key gets created upon the creation of a role");

    // Remove the role.
    \Drupal::entityTypeManager()
      ->getStorage('user_role')
      ->delete(array(Role::load($new_role_id)));

    // Test that the Role key has been deleted.
    $new_role_key = $this->keyRepository->getKey($new_role_id . "_role_key");
    $this->assertNull($new_role_key, "Role key gets deleted upon the deletion of a role");
  }

  /**
   * Test Role keys access by users from the corresponding role.
   */
  public function testRoleKeysNormalAccess() {
    // Create two new users.
    $user1 = $this->drupalCreateUser(array());
    $user2 = $this->drupalCreateUser(array());

    // Create a new role.
    $new_role_id = $this->drupalCreateRole(array());

    // Add user1 to the newly created role.
    $this->drupalLogin($this->rootUser);
    $edit = array();
    $edit['roles[' . $new_role_id . ']'] = $new_role_id;
    $this->drupalPostForm("user/" . $user1->id() . "/edit", $edit, t('Save'));

    // Test user1 is able to access the Role key because he is in the role.
    $this->drupalLogin($user1);
    $role_key_value = $this->keyRepository
      ->getKey($new_role_id . "_role_key")
      ->getKeyValue(TRUE);

    $this->assertNotEqual('', $role_key_value, "A user is able to access Role key value if he is in the role");

    // Test user2 is not able to access the Role key because he is not in the
    // role.
    $this->drupalLogin($user2);
    $role_key_value = $this->keyRepository
      ->getKey($new_role_id . "_role_key")
      ->getKeyValue(TRUE);

    $this->assertEqual('', $role_key_value, "A user is not able to access Role key value if he is not in the role");

    // Remove user1 from the newly created role.
    $this->drupalLogin($this->rootUser);
    $this->drupalPostForm("user/" . $user1->id() . "/edit", array("roles[$new_role_id]" => FALSE), t('Save'));

    // Test user1 is now not able to access the Role key because he is removed
    // from the role.
    $this->drupalLogin($user1);
    $role_key_value = $this->keyRepository
      ->getKey($new_role_id . "_role_key")
      ->getKeyValue(TRUE);
    $this->assertEqual('', $role_key_value, "A user is not able to access Role key value if he is removed from the role");
  }

  /**
   * Test Role keys access by users with "administer permissions" permission.
   */
  public function testRoleKeysPrivilegedAccess() {
    // Create an arbitrary role.
    $arbitrary_role_id = $this->drupalCreateRole(array());

    // Create a new user.
    $user = $this->drupalCreateUser(array());

    // Create a new role.
    $new_role_id = $this->drupalCreateRole(array());

    // Add user to the newly created role.
    $this->drupalLogin($this->rootUser);
    $edit = array();
    $edit['roles[' . $new_role_id . ']'] = $new_role_id;
    $this->drupalPostForm("user/" . $user->id() . "/edit", $edit, t('Save'));

    // Give user "administer permissions" permission by updating his role with
    // this permission.
    $edit = array();
    $edit[$new_role_id . '[administer permissions]'] = TRUE;
    $this->drupalPostForm('admin/people/permissions', $edit, t('Save permissions'));

    // Test that the user is now able to access any Role key value because he
    // has "administer permissions" permission.
    $this->drupalLogin($user);
    $arbitrary_role_key_value = $this->keyRepository
      ->getKey($arbitrary_role_id . "_role_key")
      ->getKeyValue(TRUE);
    $this->assertNotEqual('', $arbitrary_role_key_value, "A user is able to access any Role key value if he has 'administer permissions' permission");

    // Revoke "administer permissions" permission from the user.
    $this->drupalLogin($this->rootUser);
    $edit = array();
    $edit[$new_role_id . '[administer permissions]'] = FALSE;
    $this->drupalPostForm('admin/people/permissions', $edit, t('Save permissions'));

    // Test that the user is not able to access any arbitrary Role key now.
    $this->drupalLogin($user);
    $arbitrary_role_key_value = $this->keyRepository
      ->getKey($arbitrary_role_id . "_role_key")
      ->getKeyValue(TRUE);
    $this->assertEqual('', $arbitrary_role_key_value, "A user is not able to access an arbitrary Role key's value if he does not has 'administer permissions' permission");
  }

  /**
   * Test Role keys access when the corresponding roles have been disabled.
   */
  public function testRoleKeysDisabledAccess() {
    // Create a new user.
    $user = $this->drupalCreateUser(array());

    // Create a new role which will be disabled.
    $disabled_role_id = $this->drupalCreateRole(array());

    // Disable the newly created role.
    $config = \Drupal::service('config.factory')
      ->getEditable('pubkey_encrypt.admin_settings');
    $config->set('enabled_roles', array())
      ->save();

    // Add the user to newly created role.
    $this->drupalLogin($this->rootUser);
    $edit = array();
    $edit['roles[' . $disabled_role_id . ']'] = $disabled_role_id;
    $this->drupalPostForm("user/" . $user->id() . "/edit", $edit, t('Save'));

    // Test that the user is unable to access Role key value because the role
    // has been disabled.
    $this->drupalLogin($user);
    $role_key_value = $this->keyRepository
      ->getKey($disabled_role_id . "_role_key")
      ->getKeyValue(TRUE);
    $this->assertEqual('', $role_key_value, "A user from a role is not able to access the corresponding Role key value if the role has been disabled.");

    // Create a new privileged user.
    $admin = $this->drupalCreateUser(array());

    // Create a new role.
    $new_admin_role = $this->drupalCreateRole(array());

    // Add user to the newly created role.
    $this->drupalLogin($this->rootUser);
    $edit = array();
    $edit['roles[' . $new_admin_role . ']'] = $new_admin_role;
    $this->drupalPostForm("user/" . $admin->id() . "/edit", $edit, t('Save'));

    // Give user "administer permissions" permission by updating his role with
    // this permission.
    $edit = array();
    $edit[$new_admin_role . '[administer permissions]'] = TRUE;
    $this->drupalPostForm('admin/people/permissions', $edit, t('Save permissions'));

    // Test that a privileged user is always able to access Role key value even
    // for disabled roles.
    $this->drupalLogin($admin);
    $role_key_value = $this->keyRepository
      ->getKey($disabled_role_id . "_role_key")
      ->getKeyValue(TRUE);
    $this->assertNotEqual('', $role_key_value, "A privileged user is able to access the corresponding Role key value even if the role has been disabled.");
  }

}
