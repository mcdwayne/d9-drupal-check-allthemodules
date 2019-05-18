<?php

namespace Drupal\captcha_keypad\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests Captcha Keypad on contact pages.
 *
 * @group captcha_keypad
 */
class CaptchaKeypadTestContact extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['contact', 'captcha_keypad'];

  /**
   * A user with the 'Administer Captcha keypad' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Setup.
   */
  protected function setUp() {
    parent::setUp();

    // Create admin user.
    $this->adminUser = $this->drupalCreateUser(['administer captcha keypad'], 'Captcha Keypad Admin', TRUE);
  }

  /**
   * Test for Contact forms.
   */
  public function testCaptchaKeypadContactForm() {
    $this->drupalLogin($this->adminUser);

    // Add new contact form.
    $edit = [];
    $edit['id'] = t('feedback');
    $edit['label'] = t('Feedback');
    $edit['recipients'] = 'email@example.com';
    $this->drupalPostForm('admin/structure/contact/add', $edit, t('Save'));

    // Turn on Captcha keypad for the contact form.
    $edit = [];
    $edit['captcha_keypad_code_size'] = 99;
    $edit['captcha_keypad_forms[contact_message_personal_form]'] = 1;
    $edit['captcha_keypad_forms[contact_message_feedback_form]'] = 1;
    $this->drupalPostForm('admin/config/system/captcha_keypad', $edit, t('Save configuration'));

    $this->drupalGet('admin/config/system/captcha_keypad');
    $element = $this->xpath('//input[@type="text" and @id="edit-captcha-keypad-code-size" and @value="99"]');
    $this->assertTrue(count($element) === 1, 'The code size is correct.');

    $element = $this->xpath('//input[@type="checkbox" and @name="captcha_keypad_forms[contact_message_personal_form]" and @checked="checked"]');
    $this->assertTrue(count($element) === 1, 'Contact form is checked.');

    $element = $this->xpath('//input[@type="checkbox" and @name="captcha_keypad_forms[contact_message_feedback_form]" and @checked="checked"]');
    $this->assertTrue(count($element) === 1, 'Feedback form is checked.');

    // Submit form without captcha code.
    $edit = [];
    $edit['subject[0][value]'] = 'Foo';
    $edit['message[0][value]'] = 'Bar';
    $this->drupalPostForm('contact/feedback', $edit, t('Send message'));
    $this->assertText('Code field is required.');

    // Submit the right code.
    $edit = [];
    $edit['subject[0][value]'] = 'Foo';
    $edit['message[0][value]'] = 'Bar';
    $edit['captcha_keypad_input'] = 'testing';
    $edit['captcha_keypad_keypad_used'] = 'Yes';
    $this->drupalPostForm('contact/feedback', $edit, t('Send message'));
    $this->assertNoText('Invalid security code.');
  }

}
