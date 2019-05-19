<?php

namespace Drupal\Tests\view_profiles_perms\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests the permissions provided by view_profile_perms module.
 *
 * @package Drupal\Tests\view_profiles_perms\Functional
 *
 * @group view_profiles_perms
 */
class ViewProfilesPermsTest extends BrowserTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'view_profiles_perms_test',
  ];

  /**
   * A user with the developer role.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $developer;

  /**
   * A user with the manager role.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $manager;

  /**
   * A user with the administrator role.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $admin;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $developer = $this->drupalCreateUser();
    $developer->addRole('developer');
    $developer->save();
    $this->developer = $developer;

    $manager = $this->drupalCreateUser();
    $manager->addRole('manager');
    $manager->save();
    $this->manager = $manager;

    $this->admin = $this->drupalCreateUser([], NULL, TRUE);
  }

  /**
   * Tests view profiles permissions.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testViewProfilePerms() {
    // Assert that the roles defined by view_profiles_perms_test module get
    // their permissions generated and appear correctly in the UI.
    $assert = $this->assertSession();
    $this->drupalLogin($this->admin);
    $this->drupalGet('admin/people/permissions');
    $assert->pageTextContains('View profiles permissions');
    $assert->pageTextContains('Access Manager users profiles');
    $assert->pageTextContains('Access Developer users profiles');
    $assert->checkboxChecked('developer[access manager users profiles]');
    $assert->checkboxNotChecked('anonymous[access user profiles]');
    $assert->checkboxNotChecked('authenticated[access user profiles]');
    // Assert we are not generating permissions for authenticated nor anonymous
    // roles.
    $assert->pageTextNotContains('Access Authenticated users profiles');
    $assert->pageTextNotContains('Access Anonymous users profiles');

    // Tests for asserting access to profiles based on our permissions.
    // - Developer role has 'access manager users profiles'
    // - Manager role has no permissions
    //
    // By default Drupal only assigns 'access user profiles' to the
    // administrator role.
    // Assert Developers can access Managers profiles.
    $this->drupalLogin($this->developer);
    $this->drupalGet('user/' . $this->manager->id());
    $assert->statusCodeEquals(200);

    // Assert Managers can't access developers profiles.
    $this->drupalLogin($this->manager);
    $this->drupalGet('user/' . $this->developer->id());
    $assert->statusCodeEquals(403);

    // Assert users with more than one role, and only one with access.
    $user = $this->drupalCreateUser();
    $user->addRole('developer');
    $user->addRole('manager');
    $user->save();
    $this->drupalLogin($this->developer);
    $this->drupalGet('user/' . $user->id());
    $assert->statusCodeEquals(200);

    // Assert that the 'access user profiles' permission overrides ours.
    $this->drupalLogin($this->admin);
    $this->drupalPostForm('admin/people/permissions', ['authenticated[access user profiles]' => TRUE], 'Save permissions');
    $assert->checkboxChecked('authenticated[access user profiles]');
    // Managers should now be able to access Developers profiles.
    $this->drupalLogin($this->manager);
    $this->drupalGet('user/' . $this->developer->id());
    $assert->statusCodeEquals(200);

    // Assert any user can visit its own profile page.
    $this->drupalGet('user/' . $this->manager->id());
    $assert->statusCodeEquals(200);

    // An inactive/blocked user's profile should never be affected by our
    // permissions.
    $this->drupalLogout();
    $this->manager->block();
    $this->manager->save();
    $this->drupalLogin($this->developer);
    $this->drupalGet('user/' . $this->manager->id());
    $assert->statusCodeEquals(403);
  }

}
