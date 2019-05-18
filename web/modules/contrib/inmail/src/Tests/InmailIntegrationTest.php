<?php

namespace Drupal\inmail\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\Tests\inmail\Kernel\InmailTestHelperTrait;

/**
 * Tests the general Inmail mechanism in a typical Drupal email workflow case.
 *
 * @group inmail
 * @requires module past_db
 */
class InmailIntegrationTest extends WebTestBase {

  use DelivererTestTrait, InmailTestHelperTrait;

  /**
   * The Inmail processor service.
   *
   * @var \Drupal\inmail\MessageProcessor
   */
  protected $processor;

  /**
   * The Inmail parser service.
   *
   * @var \Drupal\inmail\MIME\MimeParser
   */
  protected $parser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'inmail_mailmute',
    'field_ui',
    'past_db',
    'past_testhidden',
    'inmail_test',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Set the Inmail processor and parser services.
    $this->processor = \Drupal::service('inmail.processor');
    $this->parser = \Drupal::service('inmail.mime_parser');

    // Make sure new users are blocked until approved by admin.
    $this->config('user.settings')->set('register', USER_REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL)->save();
    // Enable logging of raw mail messages.
    $this->config('inmail.settings')->set('log_raw_emails', TRUE)->save();
  }

  /**
   * Tests the Inmail + Mailmute mechanism with a hard bounce for a user.
   */
  public function testBounceFlow() {
    // A new user registers.
    $register_edit = array(
      // Oh no, the email address was misspelled!
      'mail' => 'usre@example.org',
      'name' => 'user',
    );
    $this->drupalPostForm('user/register', $register_edit, 'Create new account');
    $this->assertText('Your account is currently pending approval by the site administrator.');

    // Admin activates the user, thereby sending an approval email.
    $admin = $this->drupalCreateUser(array(
      'administer users',
      'administer user display',
      'administer mailmute',
    ));
    $this->drupalLogin($admin);
    $approve_edit = array(
      'status' => '1',
    );
    $this->drupalPostForm('user/2/edit', $approve_edit, 'Save');
    $this->assertMail('subject', 'Account details for user at Drupal (approved)');

    // Fake a bounce.
    $sent_mails = $this->drupalGetMails();
    $raw = static::generateBounceMessage(array_pop($sent_mails));
    // In reality the message would be passed to the processor through a drush
    // script or a mail deliverer.
    $deliverer = $this->createTestDeliverer();
    $this->processor->process('unique_key', $raw, $deliverer);
    $this->assertSuccess($deliverer, 'unique_key');

    // Check send state. Status code, date and reason are parsed from the
    // generated bounce message.
    $this->drupalGet('user/2');
    $this->assertText('Invalid address');
    $this->assertText('5.1.1');
    $this->assertText('Permanent Failure: Bad destination mailbox address');
    $this->assertText('2015-01-29 15:43:04 +01:00');
    $this->assertText('This didn\'t go too well.');

    $deliverer = $this->createTestDeliverer();
    $this->processor->process('unique_key', NULL, $deliverer);
    // Success function is never called since we pass NULL, thus state is unchanged.
    $this->assertSuccess($deliverer, '');
    $event = $this->getLastEventByMachinename('process');
    $this->assertNotNull($event);
    $this->assertEqual($event->getMessage(), 'Incoming mail, parsing failed with error: Failed to split header from body');
  }

  /**
   * Returns a sample bounce message with values from a message.
   *
   * The returned string will look like a typical hard bounce, as if the
   * original message was sent to an email server that failed to forward it to
   * its destination.
   *
   * @param array $original_message
   *   The original non-bounce message in the form used by MailManager::mail().
   *
   * @return string
   *   The generated bounce message.
   */
  protected static function generateBounceMessage(array $original_message) {
    // Set replacement variables.
    $subject = $original_message['subject'];
    $body = $original_message['body'];
    $return_path = $original_message['headers']['Return-Path'];
    $to = preg_replace('/<(.*)>/', '$1', $original_message['to']);
    $to_domain = explode('@', $to)[1];

    // Put together the headers.
    $headers = $original_message['headers'] + array(
      'To' => $to,
      'Subject' => $subject,
    );
    foreach ($headers as $name => $body) {
      $headers[$name] = "$name: $body";
    }
    $headers = implode("\n", $headers);

    // Return a fake bounce with values inserted.
    return <<<EOF
Return-Path: <>
Delivered-To: $return_path
Date: Mon, 19 Sep 2016 14:20:00 +0000
Received: some info;
  Thu, 29 Jan 2015 15:43:04 +0100
From: mta@$to_domain
To: $return_path
Subject: Mailbox $to does not exist
Content-Type: multipart/report; report-type=delivery-status; boundary="BOUNDARY"


--BOUNDARY
Content-Description: Notification
Content-Type: text/plain

This didn't go too well.

--BOUNDARY
Content-Type: message/delivery-status

Reporting-MTA: dns;$to_domain

Final-Recipient: rfc822;$to
Action: failed
Status: 5.1.1

--BOUNDARY
Content-Type: message/rfc822

$headers

$body

--BOUNDARY--

EOF;

  }

}
