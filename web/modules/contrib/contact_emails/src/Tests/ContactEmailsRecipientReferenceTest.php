<?php

namespace Drupal\contact_emails\Tests;

use Drupal\contact_emails\ContactEmailsTestBase;

/**
 * Tests contact emails recipients.
 *
 * @group ContactEmails
 */
class ContactEmailsRecipientReferenceTest extends ContactEmailsTestBase {

  public static $modules = [
    'contact',
    'contact_storage',
    'contact_emails',
    'contact_emails_test_mail_alter',
    'field_ui',
    'entity_reference',
  ];

  /**
   * Test referenced field functionality to email address.
   */
  public function testSendToReferencedField() {
    $this->addContactFormWithEmailFieldForReferencing();

    // Add the email.
    $params = [
      'subject[0][value]' => 'Contact Emails Test Form Subject',
      'message[0][value]' => 'Contact Emails Test Form Body',
      'recipient_type[0][value]' => 'reference',
      'recipient_reference[0][value]' => 'field_reference.contact_message.contact_reference_test_form.field_email_reference',
      'reply_to_type[0][value]' => 'default',
      'status[value]' => TRUE,
    ];
    $this->drupalPostForm('admin/structure/contact/manage/contact_emails_test_form/emails/add', $params, t('Save'));

    // Open the contact form on the front-end.
    $this->drupalGet('contact/contact_emails_test_form');

    // Get the reference options.
    $elements = $this->xpath('//select[@name="field_reference"]');
    $options = $this->getAllOptions($elements[0]);
    $last_option = end($options);

    // Submit the contact form on the front-end of the website.
    $params = [
      'subject[0][value]' => 'Submission Test Form Subject',
      'message[0][value]' => 'Submission Test Form Body',
      'field_reference' => $last_option->attributes()->value,
    ];
    $this->drupalPostForm(NULL, $params, t('Send message'));

    // Assert that the message to is the email of the currently logged in user.
    $this->assertText('Message-to:email-via-reference@test.com', 'Message to set successfully.');
  }

}
