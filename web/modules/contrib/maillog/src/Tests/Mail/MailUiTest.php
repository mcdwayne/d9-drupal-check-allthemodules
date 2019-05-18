<?php

/**
 * @file
 * Contains \Drupal\maillog\Tests\MailUiTest.
 */

namespace Drupal\maillog\Tests\Mail;

use Drupal\maillog\Plugin\Mail\Maillog;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the maillog plugin user interface.
 *
 * @group maillog
 */
class MailUiTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['maillog', 'user', 'system', 'views', 'contact'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Use the maillog mail plugin.
    $this->config('system.mail')->set('interface.default', 'maillog')->save();
    // The system.site.mail setting goes into the From header of outgoing mails.
    $this->config('system.site')->set('mail', 'simpletest@example.com')->save();

    // Disable e-mail sending.
    $this->config('maillog.settings')
      ->set('send', FALSE)
      ->save();
  }

  /**
   * Tests logging mail with maillog module.
   */
  public function testLogging() {
    $mail = \Drupal::service('plugin.manager.mail')->mail('maillog', 'ui_test', 'test@example.com', \Drupal::languageManager()->getCurrentLanguage(), [], 'me@example.com', FALSE);
    $mail['subject'] = 'This is a test subject.';
    $mail['body'] = 'This message is a test email body.';

    // Send the prepared email.
    $sender = new Maillog();
    $sender->mail($mail);

    // Create a user with valid permissions and go to the maillog overview page.
    $this->drupalLogin($this->drupalCreateUser(['view maillog', 'administer maillog']));
    $this->drupalGet('admin/reports/maillog');

    // Assert some values and click the subject link.
    $this->assertText('simpletest@example.com');
    $this->assertText('test@example.com');
    $this->clickLink('This is a test subject.');
    $this->assertText('This message is a test email body.');

    // Test clear log.
    $this->drupalPostForm('admin/config/development/maillog', [], 'Clear all maillog entries');
    $this->drupalPostForm(NULL, [], 'Clear');
    $this->drupalGet('admin/reports/maillog');
    $this->assertNoText('simpletest@example.com');
    $this->assertText(t('There are no mail logs in the database.'));
  }

  /**
   * Checks the drupal_set_message() for disabled mail sending.
   */
  protected function testNotice() {

    // Create a user with valid permissions and a recipient for a message.
    $recipient = $this->drupalCreateUser();
    $this->drupalLogin($this->drupalCreateUser(['access user contact forms', 'access user profiles', 'administer maillog']));

    // Send the recipient a message and check the expected notice.
    $this->drupalPostForm('user/' . $recipient->id() . '/contact', [
      'subject[0][value]' => 'Test Message',
      'message[0][value]' => 'This is a test.',
    ], t('Send message'));
    $this->clickLink('here');
    $this->assertResponse(200);
    $this->assertTitle('Maillog Settings | Drupal');
  }

  /**
   * Tests verbose output after send an email.
   */
  public function testVerboseOutput() {

    // Create a user with valid permissions.
    $user = $this->drupalCreateUser();
    $this->drupalLogin($this->drupalCreateUser([
      'access user contact forms',
      'access user profiles',
      'view maillog'
    ]));

    // Send the message.
    $this->drupalPostForm('user/' . $user->id() . '/contact', [
      'subject[0][value]' => 'Test Message',
      'message[0][value]' => 'This is a test.',
    ], t('Send message'));

    // Assert the verbose output.
    $this->assertText('A mail has been sent:');
    $this->assertRaw('[To] => ' . $user->getUsername() . '@example.com');
    $this->assertRaw('[Header] => Array');
    $this->assertRaw('[X-Mailer] =&gt; Drupal');
    $this->assertRaw('[Content-Type] =&gt; text/plain; charset=UTF-8; format=flowed; delsp=yes');
    $this->assertRaw('[Body] => Hello ' . $user->getUsername());

    // Set verbose to false.
    $this->config('maillog.settings')->set('verbose', FALSE)->save();
    $this->drupalPostForm('user/' . $user->id() . '/contact', [
      'subject[0][value]' => 'Test Message',
      'message[0][value]' => 'This is a test.',
    ], t('Send message'));

    // Assert there is no output.
    $this->assertNoText('A mail has been sent:');
    $this->assertNoRaw('[To] => ' . $user->getUsername() . '@example.com');
    $this->assertNoRaw('[Header] => Array');

    // Tests that users without permission cannot see verbose output.
    $this->config('maillog.settings')->set('verbose', TRUE)->save();
    $this->drupalLogin($this->drupalCreateUser([
      'access user contact forms',
      'access user profiles',
    ]));

    $this->drupalPostForm('user/' . $user->id() . '/contact', [
      'subject[0][value]' => 'Test Message',
      'message[0][value]' => 'This is a test.',
    ], t('Send message'));

    // Assert there is no output.
    $this->assertNoText('A mail has been sent:');
    $this->assertNoRaw('[To] => ' . $user->getUsername() . '@example.com');
    $this->assertNoRaw('[Header] => Array');
  }

}
