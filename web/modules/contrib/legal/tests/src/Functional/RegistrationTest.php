<?php

namespace Drupal\Tests\legal\Functional;

/**
 * Tests a user creating an account.
 *
 * @group legal
 */
class RegistrationTest extends LegalTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Don't require email verification.
    // Allow registration by site visitors without administrator approval.
    $this->config('user.settings')
      ->set('verify_mail', FALSE)
      ->set('register', USER_REGISTER_VISITORS)
      ->save();
  }

  /**
   * Test registration with default Legal seetings.
   */
  public function testRegistration() {

    // Test with default Legal settings.
    $edit = [
      'mail' => 'roy@example.com',
      'name' => 'Roy Batty',
      'pass[pass1]' => 'xyz',
      'pass[pass2]' => 'xyz',
      'legal_accept' => TRUE,
    ];

    // Register user.
    $this->drupalPostForm('user/register', $edit, t('Create new account'));

    // Check for success message.
    $this->assertText(t('Registration successful. You are now logged in.'));
  }

  /**
   * Test if T&Cs scroll box (textarea) displays and behaves correctly.
   */
  public function testScrollBox() {

    // Set conditions to display in an un-editable HTML text area.
    $this->config('legal.settings')
      ->set('registration_terms_style', 0)
      ->set('registration_container', 0)
      ->save();

    // Go to registration page.
    $this->drupalGet('user/register');

    // Check T&Cs displayed as textarea.
    $readonly = $this->assertSession()->elementExists('css', 'textarea#edit-conditions')->getAttribute('readonly');

    // Check textarea field is not editable.
    $this->assertEquals($readonly, 'readonly');

    // Check textarea only contains plain text.
    $this->assertSession()->elementTextContains('css', 'textarea#edit-conditions', $this->conditions_plain_text);
  }

  /**
   * Test if T&Cs scroll box (CSS) displays and behaves correctly.
   */
  public function testScrollBoxCss() {

    // Set conditions to display in a CSS scroll box.
    $this->config('legal.settings')
      ->set('registration_terms_style', 1)
      ->set('registration_container', 0)
      ->save();

    // Go to registration page.
    $this->drupalGet('user/register');

    // Check T&Cs displayed as a div with class JS will target as a scroll box.
    $this->assertSession()->elementExists('css', '#user-register-form > div.legal-terms-scroll');

    // Check scroll area contains full HTML version of T&Cs.
    $this->assertSession()->elementContains('css', '#user-register-form > div.legal-terms-scroll', $this->conditions);
  }

  /**
   * Test if T&Cs displays as HTML correctly.
   */
  public function testHtml() {

    // Set conditions to display in an un-editable HTML text area.
    $this->config('legal.settings')
      ->set('registration_terms_style', 2)
      ->set('registration_container', 0)
      ->save();

    // Go to registration page.
    $this->drupalGet('user/register');

    // Check T&Cs displayed as HTML.
    $this->assertSession()->elementContains('css', '#user-register-form > div.legal-terms', $this->conditions);
  }

  /**
   * Test if T&Cs page link displays and behaves correctly.
   */
  public function testPageLink() {

    // Set conditions to display in an un-editable HTML text area.
    $this->config('legal.settings')
      ->set('registration_terms_style', 3)
      ->set('registration_container', 0)
      ->save();

    // Go to registration page.
    $this->drupalGet('user/register');

    // Check link display.
    $this->assertSession()->elementExists('css', '#user-register-form > div.js-form-item.form-item.js-form-type-checkbox.form-type-checkbox.js-form-item-legal-accept.form-item-legal-accept > label > a');

    // Click the link.
    $this->click('#user-register-form > div.js-form-item.form-item.js-form-type-checkbox.form-type-checkbox.js-form-item-legal-accept.form-item-legal-accept > label > a');

    // Check user is on page displaying T&C.
    $current_url = $this->getUrl();
    $expected_url = $this->baseUrl . '/legal';
    $this->assertEquals($current_url, $expected_url);
  }

}
