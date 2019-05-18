<?php

namespace Drupal\Tests\administerusersbyrole\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Session\AccountInterface;

/**
 * Testing for administerusersbyrole module
 *
 * @group administerusersbyrole
 */
class AdministerusersbyroleTest extends BrowserTestBase {

  public static $modules = array('administerusersbyrole', 'user');

  protected $roles = array();
  protected $users = array();

  public function setUp() {
    parent::setUp();

    $this->createUserWithRole('noroles', array());
    $this->createRolesAndUsers('alpha', FALSE);
    $this->createRolesAndUsers('beta', TRUE);
    $this->createUserWithRole('alphabeta', array('alpha', 'beta'));

    // alphabeta_ed
    $perms = array(
      'access content',
      _administerusersbyrole_build_perm_string($this->roles['alpha'], 'edit'),
      _administerusersbyrole_build_perm_string($this->roles['beta'], 'edit'),
    );
    $this->roles['alphabeta_ed'] = $this->drupalCreateRole($perms, 'alphabeta_ed');
    $this->createUserWithRole('alphabeta_ed', array('alphabeta_ed'));

    // all_editor
    $perms = array(
      'access content',
      _administerusersbyrole_build_perm_string(AccountInterface::AUTHENTICATED_ROLE, 'edit'),
    );
    foreach ($this->roles as $roleName => $roleID) {
      $perms[] = _administerusersbyrole_build_perm_string($this->roles[$roleName], 'edit');
    }
    $this->roles['all_editor'] = $this->drupalCreateRole($perms, 'all_editor');
    $this->createUserWithRole('all_editor', array('all_editor'));

    // all_deletor
    $perms = array(
      'access content',
      _administerusersbyrole_build_perm_string(AccountInterface::AUTHENTICATED_ROLE, 'cancel'),
    );
    foreach ($this->roles as $roleName => $roleID) {
      $perms[] = _administerusersbyrole_build_perm_string($roleID, 'cancel');
    }
    $this->roles['all_deletor'] = $this->drupalCreateRole($perms, 'all_deletor');
    $this->createUserWithRole('all_deletor', array('all_deletor'));

    // creator
    $perms = array(
      'access content',
      'create users',
    );
    $this->roles['creator'] = $this->drupalCreateRole($perms, 'creator');
    $this->createUserWithRole('creator', array('creator'));
  }

  public function testPermissions() {
    $expectations = array(
      // When I'm logged in as...
      'nobody' => array(
        // ...I can perform these actions on this other user...
        'noroles'      => array('edit' => FALSE, 'cancel' => FALSE),
        'alpha'        => array('edit' => FALSE, 'cancel' => FALSE),
        'alpha_editor' => array('edit' => FALSE, 'cancel' => FALSE),
        'beta'         => array('edit' => FALSE, 'cancel' => FALSE),
        'beta_editor'  => array('edit' => FALSE, 'cancel' => FALSE),
        'alphabeta'    => array('edit' => FALSE, 'cancel' => FALSE),
        'alphabeta_ed' => array('edit' => FALSE, 'cancel' => FALSE),
        'creator'      => array('edit' => FALSE, 'cancel' => FALSE),
        'all_editor'   => array('edit' => FALSE, 'cancel' => FALSE),
        'all_deletor'  => array('edit' => FALSE, 'cancel' => FALSE),
        'create users' => FALSE,
      ),
      'noroles' => array(
        'noroles'      => array('edit' => TRUE,  'cancel' => FALSE),
        'alpha'        => array('edit' => FALSE, 'cancel' => FALSE),
        'alpha_editor' => array('edit' => FALSE, 'cancel' => FALSE),
        'beta'         => array('edit' => FALSE, 'cancel' => FALSE),
        'beta_editor'  => array('edit' => FALSE, 'cancel' => FALSE),
        'alphabeta'    => array('edit' => FALSE, 'cancel' => FALSE),
        'alphabeta_ed' => array('edit' => FALSE, 'cancel' => FALSE),
        'creator'      => array('edit' => FALSE, 'cancel' => FALSE),
        'all_editor'   => array('edit' => FALSE, 'cancel' => FALSE),
        'all_deletor'  => array('edit' => FALSE, 'cancel' => FALSE),
        'create users' => FALSE,
      ),
      'alpha' => array(
        'noroles'      => array('edit' => FALSE, 'cancel' => FALSE),
        'alpha'        => array('edit' => TRUE,  'cancel' => FALSE),
        'alpha_editor' => array('edit' => FALSE, 'cancel' => FALSE),
        'beta'         => array('edit' => FALSE, 'cancel' => FALSE),
        'beta_editor'  => array('edit' => FALSE, 'cancel' => FALSE),
        'alphabeta'    => array('edit' => FALSE, 'cancel' => FALSE),
        'alphabeta_ed' => array('edit' => FALSE, 'cancel' => FALSE),
        'creator'      => array('edit' => FALSE, 'cancel' => FALSE),
        'all_editor'   => array('edit' => FALSE, 'cancel' => FALSE),
        'all_deletor'  => array('edit' => FALSE, 'cancel' => FALSE),
        'create users' => FALSE,
      ),
      'alpha_editor' => array(
        'noroles'      => array('edit' => TRUE,  'cancel' => FALSE),
        'alpha'        => array('edit' => TRUE,  'cancel' => FALSE),
        'alpha_editor' => array('edit' => TRUE,  'cancel' => FALSE),
        'beta'         => array('edit' => FALSE, 'cancel' => FALSE),
        'beta_editor'  => array('edit' => FALSE, 'cancel' => FALSE),
        'alphabeta'    => array('edit' => FALSE, 'cancel' => FALSE),
        'alphabeta_ed' => array('edit' => FALSE, 'cancel' => FALSE),
        'creator'      => array('edit' => FALSE, 'cancel' => FALSE),
        'all_editor'   => array('edit' => FALSE, 'cancel' => FALSE),
        'all_deletor'  => array('edit' => FALSE, 'cancel' => FALSE),
        'create users' => FALSE,
      ),
      'beta' => array(
        'noroles'      => array('edit' => FALSE, 'cancel' => FALSE),
        'alpha'        => array('edit' => FALSE, 'cancel' => FALSE),
        'alpha_editor' => array('edit' => FALSE, 'cancel' => FALSE),
        'beta'         => array('edit' => TRUE,  'cancel' => FALSE),
        'beta_editor'  => array('edit' => FALSE, 'cancel' => FALSE),
        'alphabeta'    => array('edit' => FALSE, 'cancel' => FALSE),
        'alphabeta_ed' => array('edit' => FALSE, 'cancel' => FALSE),
        'creator'      => array('edit' => FALSE, 'cancel' => FALSE),
        'all_editor'   => array('edit' => FALSE, 'cancel' => FALSE),
        'all_deletor'  => array('edit' => FALSE, 'cancel' => FALSE),
        'create users' => FALSE,
      ),
      'beta_editor' => array(
        'noroles'      => array('edit' => TRUE,  'cancel' => FALSE),
        'alpha'        => array('edit' => FALSE, 'cancel' => FALSE),
        'alpha_editor' => array('edit' => FALSE, 'cancel' => FALSE),
        'beta'         => array('edit' => TRUE,  'cancel' => TRUE),
        'beta_editor'  => array('edit' => TRUE,  'cancel' => FALSE),
        'alphabeta'    => array('edit' => FALSE, 'cancel' => FALSE),
        'alphabeta_ed' => array('edit' => FALSE, 'cancel' => FALSE),
        'creator'      => array('edit' => FALSE, 'cancel' => FALSE),
        'all_editor'   => array('edit' => FALSE, 'cancel' => FALSE),
        'all_deletor'  => array('edit' => FALSE, 'cancel' => FALSE),
        'create users' => FALSE,
      ),
      'alphabeta' => array(
        'noroles'      => array('edit' => FALSE, 'cancel' => FALSE),
        'alpha'        => array('edit' => FALSE, 'cancel' => FALSE),
        'alpha_editor' => array('edit' => FALSE, 'cancel' => FALSE),
        'beta'         => array('edit' => FALSE, 'cancel' => FALSE),
        'beta_editor'  => array('edit' => FALSE, 'cancel' => FALSE),
        'alphabeta'    => array('edit' => TRUE,  'cancel' => FALSE),
        'alphabeta_ed' => array('edit' => FALSE, 'cancel' => FALSE),
        'creator'      => array('edit' => FALSE, 'cancel' => FALSE),
        'all_editor'   => array('edit' => FALSE, 'cancel' => FALSE),
        'all_deletor'  => array('edit' => FALSE, 'cancel' => FALSE),
        'create users' => FALSE,
      ),
      'alphabeta_ed' => array(
        'noroles'      => array('edit' => FALSE, 'cancel' => FALSE),
        'alpha'        => array('edit' => TRUE,  'cancel' => FALSE),
        'alpha_editor' => array('edit' => FALSE, 'cancel' => FALSE),
        'beta'         => array('edit' => TRUE,  'cancel' => FALSE),
        'beta_editor'  => array('edit' => FALSE, 'cancel' => FALSE),
        'alphabeta'    => array('edit' => TRUE,  'cancel' => FALSE),
        'alphabeta_ed' => array('edit' => TRUE,  'cancel' => FALSE),
        'creator'      => array('edit' => FALSE, 'cancel' => FALSE),
        'all_editor'   => array('edit' => FALSE, 'cancel' => FALSE),
        'all_deletor'  => array('edit' => FALSE, 'cancel' => FALSE),
        'create users' => FALSE,
      ),
      'all_editor' => array(
        'noroles'      => array('edit' => TRUE,  'cancel' => FALSE),
        'alpha'        => array('edit' => TRUE,  'cancel' => FALSE),
        'alpha_editor' => array('edit' => TRUE,  'cancel' => FALSE),
        'beta'         => array('edit' => TRUE,  'cancel' => FALSE),
        'beta_editor'  => array('edit' => TRUE,  'cancel' => FALSE),
        'alphabeta'    => array('edit' => TRUE,  'cancel' => FALSE),
        'alphabeta_ed' => array('edit' => TRUE,  'cancel' => FALSE),
        'creator'      => array('edit' => FALSE, 'cancel' => FALSE),
        'all_editor'   => array('edit' => TRUE,  'cancel' => FALSE),
        'all_deletor'  => array('edit' => FALSE, 'cancel' => FALSE),
        'create users' => FALSE,
      ),
      'all_deletor' => array(
        'noroles'      => array('edit' => FALSE, 'cancel' => TRUE),
        'alpha'        => array('edit' => FALSE, 'cancel' => TRUE),
        'alpha_editor' => array('edit' => FALSE, 'cancel' => TRUE),
        'beta'         => array('edit' => FALSE, 'cancel' => TRUE),
        'beta_editor'  => array('edit' => FALSE, 'cancel' => TRUE),
        'alphabeta'    => array('edit' => FALSE, 'cancel' => TRUE),
        'alphabeta_ed' => array('edit' => FALSE, 'cancel' => TRUE),
        'creator'      => array('edit' => FALSE, 'cancel' => FALSE),
        'all_editor'   => array('edit' => FALSE, 'cancel' => TRUE),
        'all_deletor'  => array('edit' => TRUE,  'cancel' => FALSE),
        'create users' => FALSE,
      ),
      'creator' => array(
        'noroles'      => array('edit' => FALSE, 'cancel' => FALSE),
        'alpha'        => array('edit' => FALSE, 'cancel' => FALSE),
        'alpha_editor' => array('edit' => FALSE, 'cancel' => FALSE),
        'beta'         => array('edit' => FALSE, 'cancel' => FALSE),
        'beta_editor'  => array('edit' => FALSE, 'cancel' => FALSE),
        'alphabeta'    => array('edit' => FALSE, 'cancel' => FALSE),
        'alphabeta_ed' => array('edit' => FALSE, 'cancel' => FALSE),
        'creator'      => array('edit' => TRUE,  'cancel' => FALSE),
        'all_editor'   => array('edit' => FALSE, 'cancel' => FALSE),
        'all_deletor'  => array('edit' => FALSE, 'cancel' => FALSE),
        'create users' => TRUE,
      ),
    );

    foreach ($expectations as $loginUsername => $editUsernames) {
      if ($loginUsername !== 'nobody') {
        $this->drupalLogin($this->users[$loginUsername]);
      }

      foreach ($editUsernames as $k => $v) {
        if ($k === 'create users') {
          $this->drupalGet("admin/people/create");
          $expectedResult = $v;
          if ($expectedResult) {
            $this->assertRaw('<h1 class="page-title">Add user</h1>');
          }
          else {
            $this->assertRaw('You are not authorized to access this page.');
          }
        }
        else {
          $editUsername = $k;
          $operations = $v;
          $editUid = $this->users[$editUsername]->id();
          foreach ($operations as $operation => $expectedResult) {
            $this->drupalGet("user/$editUid/$operation");
            // $loginUsername perform $operation on $editUsername
            if ($expectedResult) {
              if ($operation === 'edit') {
                $this->assertRaw("All emails from the system will be sent to this address.");
              }
              elseif ($operation === 'cancel') {
                $this->assertRaw("Are you sure you want to cancel the account <em class=\"placeholder\">$editUsername</em>?");
              }
            }
            else {
              $this->assertTrue(
                strstr($this->getRawContent(), "You do not have permission to $operation <em class=\"placeholder\">$editUsername</em>.")
                || strstr($this->getRawContent(), 'Access denied'),
                "My expectation is that $loginUsername shouldn't be able to $operation $editUsername, but it can.");
            }
          }
        }
      }

      if ($loginUsername !== 'nobody') {
        $this->drupalLogout();
      }
    }
  }

  protected function createUserWithRole($userName, $roleNames) {
    $user = $this->drupalCreateUser([], $userName);
    $this->assertTrue($user, "Unable to create user $userName.");
    foreach ($roleNames as $role) {
      $user->addRole($role);
    }
    $user->save();
    $this->users[$userName] = $user;
  }

  protected function createRolesAndUsers($roleName, $allowEditorToCancel) {
    // create basic role
    $this->roles[$roleName] = $this->drupalCreateRole(array('access content'), $roleName);
    $this->createUserWithRole($roleName, array($roleName));

    // create role to edit above role and also anyone with no custom roles.
    $perms = array(
      'access content',
      _administerusersbyrole_build_perm_string(AccountInterface::AUTHENTICATED_ROLE, 'edit'),
      _administerusersbyrole_build_perm_string($this->roles[$roleName], 'edit'),
    );
    if ($allowEditorToCancel) {
      // Don't add in "no custom roles" this time, to give better variety of testing.
      $perms[] = _administerusersbyrole_build_perm_string($this->roles[$roleName], 'cancel');
    }
    $this->roles["{$roleName}_editor"] = $this->drupalCreateRole($perms, "{$roleName}_editor");
    $this->createUserWithRole("{$roleName}_editor", array("{$roleName}_editor"));
  }

}
