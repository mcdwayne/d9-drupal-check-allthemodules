<?php

namespace Drupal\Tests\static_root_page\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that the condition plugins work.
 *
 * @group static_root_page
 */
class StaticRootPageTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'static_root_page',
    'locale',
    'language',
    'content_translation',
  ];

  /**
   * Use the standard profile.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $admin = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($admin);
    // Create FR.
    $this->drupalPostForm('/admin/config/regional/language/add', [
      'predefined_langcode' => 'fr',
    ], 'Add language');
    // Set prefixes to en and fr.
    $this->drupalPostForm('/admin/config/regional/language/detection/url', [
      'prefix[en]' => 'en',
      'prefix[fr]' => 'fr',
    ], 'Save configuration');
    // Set up URL and language selection page methods.
    $this->drupalPostForm('/admin/config/regional/language/detection', [
      'language_interface[enabled][language-url]' => TRUE,
    ], 'Save settings');
    // Turn on content translation for pages.
    $this->drupalPostform('/admin/structure/types/manage/page', [
      'language_configuration[content_translation]' => TRUE,
    ], 'Save content type');

  }

  /**
   * Test the "language prefixes" condition with Static root page disabled.
   */
  public function testDisabledModule() {
    $this->drupalPostForm('/admin/config/regional/language/detection', [
      'language_interface[enabled][static-root-page]' => FALSE,
      'language_interface[enabled][language-url]' => TRUE,
    ], 'Save settings');

    $this->drupalGet('/en');
    $this->assertSession()->elementAttributeContains('css', 'HTML', 'lang', 'en');

    $this->drupalGet('/fr');
    $this->assertSession()->elementAttributeContains('css', 'HTML', 'lang', 'fr');

    $this->drupalGet('');
    $this->assertSession()->elementAttributeContains('css', 'HTML', 'lang', 'en');
  }

  /**
   * Test the "language prefixes" condition with Static root page enabled.
   */
  public function testEnabledModule() {
    $this->drupalPostForm('/admin/config/regional/language/detection', [
      'language_interface[enabled][static-root-page]' => TRUE,
      'language_interface[enabled][language-url]' => TRUE,
    ], 'Save settings');
    $this->assertSession()->checkboxChecked('language_interface[enabled][static-root-page]');

    $this->drupalGet('/en');
    $this->assertSession()->elementAttributeContains('css', 'HTML', 'lang', 'en');

    $this->drupalGet('/fr');
    $this->assertSession()->elementAttributeContains('css', 'HTML', 'lang', 'fr');

    $this->drupalGet('');
    $this->assertSession()->pageTextNotContains('Welcome to Drupal');
    $this->assertSession()->pageTextContains('Static root page');
  }

}
