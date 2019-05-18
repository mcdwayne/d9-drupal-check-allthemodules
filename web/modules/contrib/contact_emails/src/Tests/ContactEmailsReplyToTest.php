<?php

namespace Drupal\contact_emails\Tests;

use Drupal\contact_emails\ContactEmailsTestBase;

/**
 * Tests contact emails reply to and recipients.
 *
 * @group ContactEmails
 */
class ContactEmailsReplyToTest extends ContactEmailsTestBase {

  public static $modules = [
    'contact',
    'contact_storage',
    'contact_emails',
    'contact_emails_test_mail_alter',
    'field_ui',
  ];

  /**
   * Test default functionality to email address.
   */
  public function testReplyToDefault() {
    $this->setSiteMail();

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

    // Assert that the reply-to is the default site email.
    $this->assertText('Message-reply-to:site-default-mail@test.com', 'Message to set to default successfully.');
  }

  /**
   * Test field functionality of reply-to email address.
   */
  public function testReplyToField() {
    $this->addEmailFieldToContactForm();

    // Add the email.
    $params = [
      'subject[0][value]' => 'Contact Emails Test Form Subject',
      'message[0][value]' => 'Contact Emails Test Form Body',
      'recipient_type[0][value]' => 'default',
      'reply_to_type[0][value]' => 'field',
      'reply_to_field[0][value]' => 'field_email_address',
      'status[value]' => TRUE,
    ];
    $this->drupalPostForm('admin/structure/contact/manage/contact_emails_test_form/emails/add', $params, t('Save'));

    // Submit the contact form on the front-end of the website.
    $params = [
      'subject[0][value]' => 'Submission Test Form Subject',
      'message[0][value]' => 'Submission Test Form Body',
      'field_email_address[0][value]' => 'email.in.field@test.com',
    ];
    $this->drupalPostForm('contact/contact_emails_test_form', $params, t('Send message'));

    // Assert that it says message has been sent.
    $this->assertText('Message-reply-to:email.in.field@test.com', 'Message reply-to set successfully.');
  }

  /**
   * Test form sbumitter functionality reply-to email address.
   */
  public function testReplyToFormSubmitter() {
    // Add the email.
    $params = [
      'subject[0][value]' => 'Contact Emails Test Form Subject',
      'message[0][value]' => 'Contact Emails Test Form Body',
      'recipient_type[0][value]' => 'default',
      'reply_to_type[0][value]' => 'submitter',
      'status[value]' => TRUE,
    ];
    $this->drupalPostForm('admin/structure/contact/manage/contact_emails_test_form/emails/add', $params, t('Save'));

    // Submit the contact form on the front-end of the website.
    $params = [
      'subject[0][value]' => 'Submission Test Form Subject',
      'message[0][value]' => 'Submission Test Form Body',
    ];
    $this->drupalPostForm('contact/contact_emails_test_form', $params, t('Send message'));

    // Assert that the message to is the email of the currently logged in user.
    $this->assertText('Message-reply-to:' . \Drupal::currentUser()->getEmail(), 'Message to set successfully.');
  }

  /**
   * Test manual functionality reply-to email address.
   */
  public function testReplyToManual() {
    // Add the email.
    $params = [
      'subject[0][value]' => 'Contact Emails Test Form Subject',
      'message[0][value]' => 'Contact Emails Test Form Body',
      'recipient_type[0][value]' => 'default',
      'reply_to_type[0][value]' => 'manual',
      'reply_to_email[0][value]' => 'manual-email-1@test.com',
      'status[value]' => TRUE,
    ];
    $this->drupalPostForm('admin/structure/contact/manage/contact_emails_test_form/emails/add', $params, t('Save'));

    // Submit the contact form on the front-end of the website.
    $params = [
      'subject[0][value]' => 'Submission Test Form Subject',
      'message[0][value]' => 'Submission Test Form Body',
    ];
    $this->drupalPostForm('contact/contact_emails_test_form', $params, t('Send message'));

    // Assert that the message to is the email of the currently logged in user.
    $this->assertText('Message-reply-to:manual-email-1@test.com', 'Message reply-to set successfully.');
  }

}
