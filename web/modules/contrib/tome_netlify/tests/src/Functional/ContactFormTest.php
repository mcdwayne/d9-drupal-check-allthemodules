<?php

namespace Drupal\Tests\tome_netlify\Functional;

use Drupal\contact\Entity\ContactForm;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests that form alters for Netlify works.
 *
 * @group tome_netlify
 */
class ContactFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'tome_netlify',
    'tome_static',
    'contact',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    ContactForm::create([
      'id' => 'test',
      'label' => 'test',
      'recipients' => ['admin@example.com'],
      'reply' => '',
      'weight' => 0,
      'message' => '',
      'redirect' => '',
    ])->save();
    $this->drupalLogin($this->drupalCreateUser(['administer contact forms', 'access site-wide contact form']));
  }

  /**
   * Tests that the form alters work.
   */
  public function testConfigurationForm() {
    $this->drupalGet('/admin/structure/contact/manage/test');
    $this->assertSession()->pageTextContains('Tome Netlify settings');
    $this->submitForm([
      'redirect' => '/admin/structure/contact',
      'tome_netlify[use_netlify]' => 1,
      'tome_netlify[use_captcha]' => 1,
      'tome_netlify[use_honeypot]' => 1,
    ], 'Save');
    $this->assertSession()->pageTextContains('Contact form test has been updated');
    $this->drupalGet('/contact/test');
    $this->assertSession()->elementExists('css', 'form[netlify]');
    $this->assertSession()->elementExists('css', 'form[netlify-honeypot="tome-netlify-honeypot"]');
    $this->assertSession()->elementExists('css', 'form[action="/admin/structure/contact"]');
    $this->assertSession()->elementExists('css', 'div[data-netlify-recaptcha]');
    $this->assertSession()->elementExists('css', 'input[name="tome-netlify-honeypot"]');
  }

}
