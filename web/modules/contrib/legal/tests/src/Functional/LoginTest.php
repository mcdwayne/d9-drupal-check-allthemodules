<?php

namespace Drupal\Tests\legal\Functional;

use Drupal\user\Entity\User;

/**
 * Tests a user loging into an account and accepting new T&C.
 *
 * @group legal
 */
class LoginTest extends LegalTestBase {

  /**
   * Test loging in with default Legal seetings.
   */
  public function testLogin() {

    // Test with default Legal settings.
    // Log user in.
    $this->drupalPostForm('user/login', $this->login_details, t('Log in'));

    // Check user is redirected to T&C acceptance page.
    $current_url = $this->getUrl();
    $expected_url = substr($current_url, strlen($this->baseUrl), 20);
    $this->assertEquals($expected_url, '/legal_accept?token=');
    $this->assertResponse(200);

    // Accept T&Cs and submit form.
    $edit = ['edit-legal-accept' => TRUE];
    $this->submitForm($edit, 'Confirm', 'legal-login');

    // Check user is logged in.
    $account = User::load($this->uid);
    $this->drupalUserIsLoggedIn($account);

    // Check user is redirected to their user page.
    $current_url = $this->getUrl();
    $expected_url = $this->baseUrl . '/user/' . $this->uid;
    $this->assertEquals($current_url, $expected_url);
  }

  /**
   * Test if T&Cs scroll box (textarea) displays and behaves correctly.
   */
  public function testScrollBox() {

    // Set conditions to display in an un-editable HTML text area.
    $this->config('legal.settings')
      ->set('login_terms_style', 0)
      ->set('login_container', 0)
      ->save();

    // Log user in.
    $this->drupalPostForm('user/login', $this->login_details, t('Log in'));

    // Check T&Cs displayed as textarea.
    $readonly = $this->assertSession()
      ->elementExists('css', 'textarea#edit-conditions')
      ->getAttribute('readonly');

    // Check textarea field is not editable.
    $this->assertEquals($readonly, 'readonly');

    // Check textarea only contains plain text.
    $this->assertSession()
      ->elementTextContains('css', 'textarea#edit-conditions', $this->conditions_plain_text);
  }

  /**
   * Test if T&Cs scroll box (CSS) displays and behaves correctly.
   */
  public function testScrollBoxCss() {

    // Set conditions to display in a CSS scroll box.
    $this->config('legal.settings')
      ->set('login_terms_style', 1)
      ->set('login_container', 0)
      ->save();

    // Log user in.
    $this->drupalPostForm('user/login', $this->login_details, t('Log in'));

    // Check T&Cs displayed as a div with class JS will target as a scroll box.
    $this->assertSession()
      ->elementExists('css', '#legal-login > div.legal-terms-scroll');

    // Check scroll area contains full HTML version of T&Cs.
    $this->assertSession()
      ->elementContains('css', '#legal-login > div.legal-terms-scroll', $this->conditions);
  }

  /**
   * Test if T&Cs displays as HTML.
   */
  public function testHtml() {

    // Set conditions to display as HTML.
    $this->config('legal.settings')
      ->set('login_terms_style', 2)
      ->set('login_container', 0)
      ->save();

    $this->drupalPostForm('user/login', $this->login_details, t('Log in'));

    // Check T&Cs displayed as HTML.
    $this->assertSession()
      ->elementContains('css', '#legal-login > div.legal-terms', $this->conditions);
  }

  /**
   * Test if T&Cs page link displays and behaves correctly.
   */
  public function testPageLink() {

    // Set to display as a link to T&Cs.
    $this->config('legal.settings')
      ->set('login_terms_style', 3)
      ->set('login_container', 0)
      ->save();

    $this->drupalPostForm('user/login', $this->login_details, t('Log in'));

    // Check link display.
    $this->assertSession()
      ->elementExists('css', '#legal-login > div.js-form-item.form-item.js-form-type-checkbox.form-type-checkbox.js-form-item-legal-accept.form-item-legal-accept > label > a');

    // Click the link.
    $this->click('#legal-login > div.js-form-item.form-item.js-form-type-checkbox.form-type-checkbox.js-form-item-legal-accept.form-item-legal-accept > label > a');

    // Check user is on page displaying T&C.
    $current_url = $this->getUrl();
    $expected_url = $this->baseUrl . '/legal';
    $this->assertEquals($current_url, $expected_url);
  }

}


