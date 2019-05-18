<?php
/**
 * @file
 * Contains \Drupal\mailmute\Tests\MailmuteWebTest.
 */

namespace Drupal\mailmute\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test the Mailmute UI.
 *
 * @group mailmute
 */
class MailmuteWebTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('mailmute', 'mailmute_test', 'field_ui');

  /**
   * Test the Mailmute UI.
   */
  public function testField() {
    // Log in admin.
    $admin = $this->drupalCreateUser(array('administer mailmute'));
    $this->drupalLogin($admin);

    // Check the edit form.
    $this->drupalGet('user/' . $admin->id() . '/edit');
    $this->assertField('sendstate[plugin_id]', 'Send state field found on user form');
    $this->assertOption('sendstate[plugin_id]', 'onhold', NULL, '"On hold" option found on user form');
    $this->assertOption('sendstate[plugin_id]', 'send', NULL, '"Send" option found on user form');
    $this->assertOption('sendstate[plugin_id]', 'send', TRUE, '"Send" option selected by default');

    $edit = array(
      'sendstate[plugin_id]' => 'onhold',
    );
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertOption('sendstate[plugin_id]', 'onhold', TRUE, '"On hold" option selection saved');

    // Check the user view.
    $this->drupalGet('user');
    $this->assertText('Send emails');
    $this->assertText('On hold');
  }

  /**
   * Test the limitations of different permissions.
   */
  public function testPermissions() {
    // Normal user can neither view or edit.
    $user1 = $this->drupalCreateUser();
    $this->drupalLogin($user1);
    $this->assertText('Member for');
    $this->assertNoText('Send emails');

    $this->drupalGet('user/' . $user1->id() . '/edit');
    $this->assertNoFieldByXPath('//details/summary/text()', 'Send emails');

    // User with 'change own' permission can view and edit own state.
    $this->drupalLogout();
    $user2 = $this->drupalCreateUser(array('change own send state', 'access user profiles'));
    $this->drupalLogin($user2);
    $this->assertText('Member for');
    $this->assertText('Send emails');

    $this->drupalGet('user/' . $user2->id() . '/edit');
    $this->assertFieldByXPath('//details/summary/text()', 'Send emails');

    // But not the state of other users.
    $this->drupalGet('user/' . $user1->id());
    $this->assertText('Member for');
    $this->assertNoText('Send emails');

    // User with admin permission can view and edit own state.
    $this->drupalLogout();
    $user3 = $this->drupalCreateUser(array('administer mailmute', 'access user profiles'));
    $this->drupalLogin($user3);
    $this->assertText('Member for');
    $this->assertText('Send emails');

    $this->drupalGet('user/' . $user3->id() . '/edit');
    $this->assertFieldByXPath('//details/summary/text()', 'Send emails');

    // And the state of other users.
    $this->drupalGet('user/' . $user1->id());
    $this->assertText('Member for');
    $this->assertText('Send emails');
  }

  /**
   * Test that some states require admin permission.
   */
  public function testAdminStates() {
    // Log in admin user.
    $admin = $this->drupalCreateUser(array('administer mailmute'));
    $this->drupalLogin($admin);

    // Check that the admin state is selectable.
    $this->drupalGet('user/' . $admin->id() . '/edit');
    $xpath = "//select[@name='sendstate[plugin_id]']//option[@value='admin_state']";
    $this->assertFieldByXPath($xpath);

    // Log in non-admin user.
    $this->drupalLogout();
    $user = $this->drupalCreateUser(array('change own send state'));
    $this->drupalLogin($user);

    // Check that the admin state is not selectable.
    $this->drupalGet('user/' . $user->id() . '/edit');
    $this->assertNoFieldByXPath($xpath);
  }

  /**
   * Test the send state overview page.
   */
  public function testStatesOverview() {
    $admin = $this->drupalCreateUser(array('administer mailmute'));
    $this->drupalLogin($admin);
    $this->drupalGet('admin/config/people/mailmute/sendstates');

    $this->assertText('On hold');
    $this->assertText('The address owner requested muting until further notice.');

    $this->assertText('Send');
    $this->assertText('Messages are not suppressed. This is the default state.');

    // The test state has Send as parent, and should be indented.
    $this->assertRaw('<div class="js-indentation indentation">&nbsp;</div>Admin state');
  }

  /**
   * Tests that notification message display follows configuration.
   */
  public function testNotificationMessage() {
    // Create a muted user.
    $user = $this->drupalCreateUser();
    $admin = $this->drupalCreateUser(array('administer users', 'administer mailmute'));
    $this->drupalLogin($admin);
    $this->drupalPostForm('user/' . $user->id() . '/edit', array('sendstate[plugin_id]' => 'onhold'), 'Save');
    $this->drupalLogout();
    $config = \Drupal::configFactory()->getEditable('mailmute.settings');

    // This path, defined in the routing of the mailmute_test module, sends an
    // email to the given address.
    $mail_path = 'mailmute_test_mail/' . $user->getEmail();
    // This matches the suppression message.
    $suppress_message = 'Message to ' . $user->getEmail() . ' suppressed';

    // Current user should see message if setting is 'current' or 'always'.
    $this->drupalLogin($user);

    $config->set('show_message', 'always')->save();
    $this->drupalGet($mail_path);
    $this->assertResponse(200);
    $this->assertText($suppress_message);

    $config->set('show_message', 'current')->save();
    $this->drupalGet($mail_path);
    $this->assertResponse(200);
    $this->assertText($suppress_message);

    $config->set('show_message', 'never')->save();
    $this->drupalGet($mail_path);
    $this->assertResponse(200);
    $this->assertNoText($suppress_message);

    // Another user should only see message if setting is 'always'.
    $this->drupalLogout();
    $this->drupalLogin($admin);

    $config->set('show_message', 'always')->save();
    $this->drupalGet($mail_path);
    $this->assertResponse(200);
    $this->assertText($suppress_message);

    $config->set('show_message', 'current')->save();
    $this->drupalGet($mail_path);
    $this->assertResponse(200);
    $this->assertNoText($suppress_message);

    $config->set('show_message', 'never')->save();
    $this->drupalGet($mail_path);
    $this->assertResponse(200);
    $this->assertNoText($suppress_message);
  }

  /**
   * Asserts that an option element exists within a select element.
   *
   * @param string $select_field
   *   The name of the expected select field.
   * @param string $option_key
   *   The value of the expected option element.
   * @param bool|null $selected
   *   (optional) To assert that the option is selected, set to TRUE. To assert
   *   not selected, set to FALSE. Omit to not assert on the selected state.
   * @param string $message
   *   (optional) A message to display with the assertion.
   * @param string $group
   *   (optional) The group this message is in.
   *
   * @return bool
   *   TRUE if the assertion passed, FALSE if it failed.
   */
  protected function assertOption($select_field, $option_key, $selected = NULL, $message = '', $group = 'Other') {
    $match_selected = isset($selected) ? ($selected ? ' and @selected' : ' and not(@selected)') : '';
    $xpath = "//select[@name='$select_field']//option[@value='$option_key'$match_selected]";
    return $this->assertFieldByXPath($xpath, NULL, $message, $group);
  }

}
