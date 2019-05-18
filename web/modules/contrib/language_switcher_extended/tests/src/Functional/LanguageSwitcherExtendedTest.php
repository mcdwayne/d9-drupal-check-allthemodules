<?php

namespace Drupal\Tests\language_switcher_extended\Functional;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for the language_switcher_extended feature.
 *
 * @group language_switcher_extended
 */
class LanguageSwitcherExtendedTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'locale',
    'language',
    'node',
    'content_translation',
    'block',
    'language_switcher_extended',
  ];

  /**
   * The node object used in the test.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Login as root user.
    $this->drupalLogin($this->rootUser);

    // Add another language.
    $edit = [
      'predefined_langcode' => 'de',
    ];
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add language'));

    // Enable URL language detection and selection.
    $edit = ['language_interface[enabled][language-url]' => '1'];
    $this->drupalPostForm('admin/config/regional/language/detection', $edit, t('Save settings'));

    // Enable the language switching block.
    $block = $this->drupalPlaceBlock('language_block:' . LanguageInterface::TYPE_INTERFACE, [
      'id' => 'test_language_block',
    ]);

    // Create node type, which has english language as default.
    $this->createContentType([
      'name' => 'Article',
      'type' => 'article',
    ]);

    // Enable content translation.
    $this->drupalGet('admin/config/regional/content-language');
    $edit = [
      'entity_types[node]' => TRUE,
      'settings[node][article][translatable]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));

    // Create a node without a translation.
    $this->node = $this->createNode([
      'type' => 'article',
      'title' => 'Test article (en)',
      'langcode' => 'en',
    ]);
    $this->node->save();
  }

  /**
   * Tests, that language switcher link always link to frontpage.
   */
  public function testLinkAlwaysToFrontpage() {
    // Open the module's configuration page.
    $this->drupalGet('admin/config/regional/language/language-switcher-extended');

    // Configure to always link to the front.
    $edit = ['mode' => 'always_link_to_front'];
    $this->submitForm($edit, 'Save configuration');

    // Open the node without a translation.
    $this->drupalGet('node/1');

    // Verify, that all language switcher links lead to the frontpage.
    $this->assertSession()
      ->elementAttributeContains('css', '#block-test-language-block li[hreflang="en"] a', 'data-drupal-link-system-path', '<front>');
    $this->assertSession()
      ->elementAttributeContains('css', '#block-test-language-block li[hreflang="de"] a', 'data-drupal-link-system-path', '<front>');
  }

  /**
   * Tests, to link language switcher item for an untranslated entity to front.
   */
  public function testLinkUntranslatedEntityLanguageToFront() {
    // Open the module's configuration page.
    $this->drupalGet('admin/config/regional/language/language-switcher-extended');

    // Configure to link untranslated translations to the front.
    $edit = [
      'mode' => 'process_untranslated',
      'untranslated_handler' => 'link_to_front',
    ];
    $this->submitForm($edit, 'Save configuration');

    // Open the node without a translation.
    $this->drupalGet('node/1');

    // Verify, that the untranslated language switcher links lead to the
    // frontpage.
    $this->assertSession()
      ->elementAttributeContains('css', '#block-test-language-block li[hreflang="en"] a', 'data-drupal-link-system-path', 'node/1');
    $this->assertSession()
      ->elementAttributeContains('css', '#block-test-language-block li[hreflang="de"] a', 'data-drupal-link-system-path', '<front>');
  }

  /**
   * Tests, to hide language switcher item for an untranslated entity.
   */
  public function testHideUntranslatedEntityLanguage() {
    // Open the module's configuration page.
    $this->drupalGet('admin/config/regional/language/language-switcher-extended');

    // Configure to hide the link for untranslated entities.
    $edit = [
      'mode' => 'process_untranslated',
      'untranslated_handler' => 'hide_link',
    ];
    $this->submitForm($edit, 'Save configuration');

    // Open the node without a translation.
    $this->drupalGet('node/1');

    // Verify, that the untranslated language switcher is hidden.
    $this->assertSession()
      ->elementAttributeContains('css', '#block-test-language-block li[hreflang="en"] a', 'data-drupal-link-system-path', 'node/1');
    $this->assertSession()
      ->elementNotExists('css', '#block-test-language-block li[hreflang="de"]');
    $this->assertSession()
      ->elementsCount('css', '#block-test-language-block li', 1);

    // Configure to hide a single remaining language switcher link.
    $this->drupalGet('admin/config/regional/language/language-switcher-extended');
    $edit = [
      'mode' => 'process_untranslated',
      'untranslated_handler' => 'hide_link',
      'hide_single_link' => 1,
    ];
    $this->submitForm($edit, 'Save configuration');

    // Open the node without a translation.
    $this->drupalGet('node/1');

    // Verify, that there is no language switcher item anymore.
    $this->assertSession()
      ->elementsCount('css', '#block-test-language-block li', 0);
  }

  /**
   * Tests, that a translated entity language switcher item is visible.
   */
  public function testTranslatedLanguageSwitcherItemIsVisible() {
    // Open the module's configuration page.
    $this->drupalGet('admin/config/regional/language/language-switcher-extended');

    // Configure to hide the link for untranslated entities.
    $edit = [
      'mode' => 'process_untranslated',
      'untranslated_handler' => 'hide_link',
    ];
    $this->submitForm($edit, 'Save configuration');

    // Add a new translation for the node.
    $translation = $this->node->addTranslation('de', $this->node->toArray());
    $translation->save();

    // Open the node.
    $this->drupalGet('node/1');

    // Verify, that the translated language switcher item is shown.
    $this->assertSession()
      ->elementAttributeContains('css', '#block-test-language-block li[hreflang="en"] a', 'data-drupal-link-system-path', 'node/1');
    $this->assertSession()
      ->elementAttributeContains('css', '#block-test-language-block li[hreflang="de"] a', 'data-drupal-link-system-path', 'node/1');
    $this->assertSession()
      ->elementAttributeContains('css', '#block-test-language-block li[hreflang="de"] a', 'href', 'de/node/1');
  }

  /**
   * Tests, to show/no link language switcher item for an untranslated entity.
   */
  public function testShowButNotLinkUntranslatedEntityLanguage() {
    // Open the module's configuration page.
    $this->drupalGet('admin/config/regional/language/language-switcher-extended');

    // Configure to show untranslated translations without a link.
    $edit = [
      'mode' => 'process_untranslated',
      'untranslated_handler' => 'no_link',
    ];
    $this->submitForm($edit, 'Save configuration');

    // Open the node without a translation.
    $this->drupalGet('node/1');

    // Verify, that the untranslated language switcher is shown, but has no
    // link.
    $this->assertSession()
      ->elementAttributeContains('css', '#block-test-language-block li[hreflang="en"] a', 'data-drupal-link-system-path', 'node/1');
    $this->assertSession()
      ->elementNotExists('css', '#block-test-language-block li.de a');
    $this->assertSession()
      ->elementTextContains('css', '#block-test-language-block li.de', 'German');
  }

}
