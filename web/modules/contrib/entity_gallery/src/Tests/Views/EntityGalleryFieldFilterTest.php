<?php

namespace Drupal\entity_gallery\Tests\Views;

use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests entity gallery field filters with translations.
 *
 * @group entity_gallery
 */
class EntityGalleryFieldFilterTest extends EntityGalleryTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('language');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_field_filters');

  /**
   * List of entity gallery titles by language.
   *
   * @var array
   */
  public $entityGalleryTitles = [];

  function setUp() {
    parent::setUp();

    // Create Page gallery type.
    if ($this->profile != 'standard') {
      $this->drupalCreateGalleryType(array('type' => 'page', 'name' => 'Basic page'));
    }

    // Add two new languages.
    ConfigurableLanguage::createFromLangcode('fr')->save();
    ConfigurableLanguage::createFromLangcode('es')->save();

    // Set up entity gallery titles.
    $this->entityGalleryTitles = array(
      'en' => 'Food in Paris',
      'es' => 'Comida en Paris',
      'fr' => 'Nouriture en Paris',
    );

    // Create entity gallery with translations.
    $entity_gallery = $this->drupalCreateEntityGallery(['title' => $this->entityGalleryTitles['en'], 'langcode' => 'en', 'type' => 'page', 'body' => [['value' => $this->entityGalleryTitles['en']]]]);
    foreach (array('es', 'fr') as $langcode) {
      $translation = $entity_gallery->addTranslation($langcode, ['title' => $this->entityGalleryTitles[$langcode]]);
    }
    $entity_gallery->save();
  }

  /**
   * Tests title filters.
   */
  public function testFilters() {
    // Test the title filter page, which filters for title contains 'Comida'.
    // Should show just the Spanish translation, once.
    $this->assertPageCounts('test-title-filter', array('es' => 1, 'fr' => 0, 'en' => 0), 'Comida title filter');

    // Test the title Paris filter page, which filters for title contains
    // 'Paris'. Should show each translation once.
    $this->assertPageCounts('test-title-paris', array('es' => 1, 'fr' => 1, 'en' => 1), 'Paris title filter');
  }

  /**
   * Asserts that the given entity gallery translation counts are correct.
   *
   * @param string $path
   *   Path of the page to test.
   * @param array $counts
   *   Array whose keys are languages, and values are the number of times
   *   that translation should be shown on the given page.
   * @param string $message
   *   Message suffix to display.
   */
  protected function assertPageCounts($path, $counts, $message) {
    // Disable read more links.
    entity_get_display('entity_gallery', 'page', 'teaser')->removeComponent('links')->save();

    // Get the text of the page.
    $this->drupalGet($path);
    $text = $this->getTextContent();

    // Check the counts.
    foreach ($counts as $langcode => $count) {
      $this->assertEqual(substr_count($text, $this->entityGalleryTitles[$langcode]), $count, 'Translation ' . $langcode . ' has count ' . $count . ' with ' . $message);
    }
  }
}
