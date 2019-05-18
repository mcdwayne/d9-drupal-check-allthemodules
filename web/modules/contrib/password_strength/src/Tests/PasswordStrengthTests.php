<?php

namespace Drupal\password_strength\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests password strength behaviors from Password Strength library.
 *
 * @group password_strength
 */
class PasswordStrengthTests extends WebTestBase {

  public static $modules = array('password_policy', 'password_strength');

  /**
   * Test password strength behaviors.
   */
  function testPasswordStrengthTests() {
    // Create user with permission to create policy.
    $user1 = $this->drupalCreateUser(array(
      'administer site configuration',
      'administer users',
      'administer permissions',
    ));
    $this->drupalLogin($user1);

    $user2 = $this->drupalCreateUser();

    // Create role.
    $rid = $this->drupalCreateRole(array());

    // Set role for user.
    $edit = [
      'roles[' . $rid . ']' => $rid,
    ];
    $this->drupalPostForm("user/" . $user2->id() . "/edit", $edit, t('Save'));

    // Create new password reset policy for role.
    $this->drupalGet("admin/config/security/password-policy/add");
    $edit = [
      'id' => 'test',
      'label' => 'test',
      'password_reset' => '1',
    ];
    // Set reset and policy info.
    $this->drupalPostForm(NULL, $edit, 'Next');

    $this->assertText('No constraints have been configured.');

    // Fill out length constraint for test policy.
    $edit = [
      'strength_score' => '4',
    ];
    $this->drupalPostForm('admin/config/system/password_policy/constraint/add/test/password_strength_constraint', $edit, 'Save');

    $this->assertText('password_strength_constraint');
    $this->assertText('Password Strength minimum score of 4');

    // Go to the next page.
    $this->drupalPostForm(NULL, [], 'Next');
    // Set the roles for the policy.
    $edit = [
      'roles[' . $rid . ']' => $rid,
    ];
    $this->drupalPostForm(NULL, $edit, 'Finish');

    $this->assertText('Saved the test Password Policy.');

    $this->drupalLogout();
    $this->drupalLogin($user2);

    // Change own password with one not very complex.
    $edit = array();
    $edit['pass[pass1]'] = '1';
    $edit['pass[pass2]'] = '1';
    $edit['current_pass'] = $user2->pass_raw;
    $this->drupalPostForm("user/" . $user2->id() . "/edit", $edit, t('Save'));

    // Verify we see an error.
    $this->assertText('The password does not satisfy the password policies');

    // Change own password with one strong enough.
    $edit = array();
    $edit['pass[pass1]'] = 'aV3ryC*mplexPassword1nd33d!';
    $edit['pass[pass2]'] = 'aV3ryC*mplexPassword1nd33d!';
    $edit['current_pass'] = $user2->pass_raw;
    $this->drupalPostForm("user/" . $user2->id() . "/edit", $edit, t('Save'));

    // Verify we see do not error.
    $this->assertNoText('The password does not satisfy the password policies');

    $this->drupalLogout();
  }
}
