<?php

namespace Drupal\username_reminder\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests requesting a username reminder.
 *
 * @group UsernameReminder
 */
class UsernameReminderTest extends WebTestBase {

  public static $modules = ['username_reminder'];

  /**
   * Tests requesting username for unknown email address.
   */
  public function testUsernameReminderUnknownEmail() {
    $email = 'foo@example.com';
    $edit = ['email' => $email];
    $this->drupalPostForm('user/username', $edit, t('Submit'));
    $this->assertRaw(t('Sorry, %email is not a recognized email address.', ['%email' => $email]), 'Message indicates unknown email address.');
  }

  /**
   * Tests requesting username for active account.
   */
  public function testUsernameReminderActiveAccount() {
    $account = $this->drupalCreateUser();
    $edit = ['email' => $account->getEmail()];
    $this->drupalPostForm('user/username', $edit, t('Submit'));
    $this->assertText(t('Your username has been sent to your email address.'), 'Message indicates username reminder sent.');
    $token_service = \Drupal::token();
    $expected_email = [
      'subject' => $token_service->replace(t("[site:name] username reminder")),
      'body' => $token_service->replace(t("Your username: [user:name]\n\n--  [site:name] team"), ['user' => $account]) . "\n",
    ];
    $this->assertMail('subject', $expected_email['subject'], 'Sent email subject matches expectation.');
    $this->assertMail('body', $expected_email['body'], 'Sent email body matches expectation.');
  }

  /**
   * Tests requesting username for inactive account.
   */
  public function testUsernameReminderInactiveAccount() {
    $account = $this->drupalCreateUser();
    $account->block();
    $account->save();
    $edit = ['email' => $account->getEmail()];
    $this->drupalPostForm('user/username', $edit, t('Submit'));
    $this->assertRaw(t('Sorry, %email is not a recognized email address.', ['%email' => $account->getEmail()]), 'Message indicates unknown email address.');
  }

  /**
   * Tests customizing username reminder email.
   */
  public function testCustomUsernameReminder() {
    // Customize email.
    $admin = $this->drupalCreateUser(['administer username reminder']);
    $this->drupalLogin($admin);
    $edit = [
      'username_reminder_subject' => 'foo',
      'username_reminder_body' => 'bar: [user:name]',
    ];
    $this->drupalPostForm('admin/config/people/username_reminder', $edit, t('Save configuration'));
    $this->drupalLogout();

    // Request username reminder.
    $account = $this->drupalCreateUser();
    $edit = ['email' => $account->getEmail()];
    $this->drupalPostForm('user/username', $edit, t('Submit'));
    $this->assertText(t('Your username has been sent to your email address.'), 'Message indicates username reminder sent.');
    $token_service = \Drupal::token();
    $expected_email = [
      'subject' => 'foo',
      'body' => $token_service->replace("bar: [user:name]", ['user' => $account]) . "\n",
    ];
    $this->assertMail('subject', $expected_email['subject'], 'Sent email subject matches expectation.');
    $this->assertMail('body', $expected_email['body'], 'Sent email body matches expectation.');
  }

}
