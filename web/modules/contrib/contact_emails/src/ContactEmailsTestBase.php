<?php

namespace Drupal\contact_emails;

use Drupal\simpletest\WebTestBase;

/**
 * Base class for contact emails tests.
 */
class ContactEmailsTestBase extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->createUserAndLogin();
    $this->createBaseContactForm();
  }

  /**
   * Creates the admin user and logs in.
   */
  protected function createUserAndLogin() {
    // Create the user.
    $this->adminUser = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Creates a base contact form for use in all tests.
   */
  protected function createBaseContactForm() {
    // Create a contact form.
    $params = [
      'label' => 'Contact Emails Test Form',
      'id' => 'contact_emails_test_form',
      'message' => 'Your message has been sent.',
      'recipients' => 'test@example.com',
      'contact_storage_submit_text' => 'Send message',
    ];
    $this->drupalPostForm('admin/structure/contact/add', $params, t('Save'));
  }

  /**
   * Set the site email.
   */
  protected function setSiteMail() {
    $settings['config']['system.site']['mail'] = (object) [
      'value' => 'site-default-mail@test.com',
      'required' => TRUE,
    ];
    $this->writeSettings($settings);
  }

  /**
   * Helper function to add an email field to the contact form.
   */
  protected function addEmailFieldToContactForm() {
    // Add the field.
    $params = [
      'new_storage_type' => 'email',
      'label' => 'Email address',
      'field_name' => 'email_address',
    ];
    $this->drupalPostForm('admin/structure/contact/manage/contact_emails_test_form/fields/add-field', $params, t('Save and continue'));

    // Save the default base field settings.
    $this->drupalPostForm(NULL, [], t('Save field settings'));

    // Save the field settings.
    $this->drupalPostForm(NULL, [], t('Save settings'));

    // Assert that the field exists.
    $this->assertText('field_email_address', 'Field email address exists.');
  }

  /**
   * Helper function to create additional contact form to test referencing.
   */
  protected function addContactFormWithEmailFieldForReferencing() {
    // Create a contact form.
    $params = [
      'label' => 'Contact Reference Test Form',
      'id' => 'contact_reference_test_form',
      'message' => 'Your message has been sent.',
      'recipients' => 'test@example.com',
      'contact_storage_submit_text' => 'Send message',
    ];
    $this->drupalPostForm('admin/structure/contact/add', $params, t('Save'));

    // Add an email field to be referenced.
    $params = [
      'new_storage_type' => 'email',
      'label' => 'Email reference',
      'field_name' => 'email_reference',
    ];
    $this->drupalPostForm('admin/structure/contact/manage/contact_reference_test_form/fields/add-field', $params, t('Save and continue'));

    // Save the default base field settings.
    $this->drupalPostForm(NULL, [], t('Save field settings'));

    // Save the field settings.
    $this->drupalPostForm(NULL, [], t('Save settings'));

    // Assert that the field exists.
    $this->assertText('field_email_reference', 'Field email address exists.');

    // Add an email field to reference the new form's field.
    $params = [
      'new_storage_type' => 'entity_reference',
      'label' => 'Reference',
      'field_name' => 'reference',
    ];
    $this->drupalPostForm('admin/structure/contact/manage/contact_emails_test_form/fields/add-field', $params, t('Save and continue'));

    // Save the default base field settings.
    $params = [
      'settings[target_type]' => 'contact_message',
    ];
    $this->drupalPostForm(NULL, $params, t('Save field settings'));

    // Save the field settings.
    $params = [
      'settings[handler_settings][target_bundles][contact_reference_test_form]' => 'contact_reference_test_form',
    ];
    $this->drupalPostForm(NULL, $params, t('Save settings'));

    // Assert that the field exists.
    $this->assertText('field_reference', 'Field reference exists.');

    // Save the display settings to make the reference a simple select.
    $params = [
      'fields[field_reference][type]' => 'options_select',
    ];
    $this->drupalPostForm('admin/structure/contact/manage/contact_emails_test_form/form-display', $params, t('Save'));

    // Submit the refernce contact form on the front-end of the website.
    $params = [
      'subject[0][value]' => 'Submission Test Form Subject',
      'message[0][value]' => 'Submission Test Form Body',
      'field_email_reference[0][value]' => 'email-via-reference@test.com',
    ];
    $this->drupalPostForm('contact/contact_reference_test_form', $params, t('Send message'));

    // Assert that it says message has been sent.
    $this->assertText('Your message has been sent.', 'Message sent successfully.');
  }

}
