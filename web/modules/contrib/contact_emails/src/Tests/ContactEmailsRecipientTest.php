<?php

namespace Drupal\contact_emails\Tests;

use Drupal\contact_emails\ContactEmailsTestBase;

/**
 * Tests contact emails recipients.
 *
 * @group ContactEmails
 */
class ContactEmailsRecipientTest extends ContactEmailsTestBase {

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
  public function testSendToDefault() {
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

    // Assert that the to is the default site email.
    $this->assertText('Message-to:site-default-mail@test.com', 'Message to set to default successfully.');
  }

  /**
   * Test field functionality to email address.
   */
  public function testSendToField() {
    $this->addEmailFieldToContactForm();

    // Add the email.
    $params = [
      'subject[0][value]' => 'Contact Emails Test Form Subject',
      'message[0][value]' => 'Contact Emails Test Form Body',
      'recipient_type[0][value]' => 'field',
      'recipient_field[0][value]' => 'field_email_address',
      'reply_to_type[0][value]' => 'default',
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

    // Assert that the message to is the value of the field.
    $this->assertText('Message-to:email.in.field@test.com', 'Message to set successfully.');
  }

  /**
   * Test form submitter functionality to email address.
   */
  public function testSendToFormSubmitter() {
    // Add the email.
    $params = [
      'subject[0][value]' => 'Contact Emails Test Form Subject',
      'message[0][value]' => 'Contact Emails Test Form Body',
      'recipient_type[0][value]' => 'submitter',
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

    // Assert that the message to is the email of the currently logged in user.
    $this->assertText('Message-to:' . \Drupal::currentUser()->getEmail(), 'Message to set successfully.');
  }

  /**
   * Test manual functionality to email address.
   */
  public function testSendToManual() {
    // Add the email.
    $params = [
      'subject[0][value]' => 'Contact Emails Test Form Subject',
      'message[0][value]' => 'Contact Emails Test Form Body',
      'recipient_type[0][value]' => 'manual',
      'recipients[0][value]' => 'manual-email-1@test.com, manual-email-2@test.com',
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

    // Assert that the message to is the email of the currently logged in user.
    $this->assertText('Message-to:manual-email-1@test.com, manual-email-2@test.com', 'Message to set successfully.');
  }

}
