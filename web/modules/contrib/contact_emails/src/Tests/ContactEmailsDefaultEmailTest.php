<?php

namespace Drupal\contact_emails\Tests;

use Drupal\contact_emails\ContactEmailsTestBase;

/**
 * Tests contact emails sending functionality.
 *
 * @group ContactEmails
 */
class ContactEmailsDefaultEmailTest extends ContactEmailsTestBase {

  public static $modules = [
    'contact',
    'contact_storage',
    'contact_emails',
    'contact_emails_test_mail_alter',
  ];

  /**
   * Test default functionality of sending an email.
   */
  public function testSendEmail() {
    // Add the email.
    $params = [
      'subject[0][value]' => 'Contact Emails Test Form Subject',
      'message[0][value]' => 'Contact Emails Test Form Body',
      'recipient_type[0][value]' => 'default',
      'reply_to_type[0][value]' => 'default',
      'status[value]' => TRUE,
    ];
    $this->drupalPostForm('admin/structure/contact/manage/contact_emails_test_form/emails/add', $params, t('Save'));

    // Submit the contact form on the front-end of the website.
    $params = [
      'subject[0][value]' => 'Submission Test Form Subject',
      'message[0][value]' => 'Submission Test Form Body',
    ];
    $this->drupalPostForm('contact/contact_emails_test_form', $params, t('Send message'));

    // Assert that it says message has been sent.
    $this->assertText('Your message has been sent.', 'Message sent successfully.');

    // Assert subject and body.
    $this->assertText('Contact Emails Test Form Subject', 'Message subject set successfully.');
    $this->assertText('Contact Emails Test Form Body', 'Message body set successfully.');
  }

}
