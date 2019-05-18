<?php

namespace Drupal\sitemap\Tests;

/**
 * Tests the display of taxonomies based on sitemap settings.
 *
 * @group sitemap
 */
class SitemapTaxonomyTest extends SitemapTaxonomyTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['sitemap', 'node', 'taxonomy'];

  /**
   * A vocabulary entity.
   *
   * @var \Drupal\taxonomy\Entity\Vocabulary
   */
  protected $vocabularyToDelete;

  /**
   * Tests vocabulary description.
   */
  public function testVocabularyDescription() {
    // Assert that vocabulary description is not included if no tags are
    // displayed.
    $this->drupalGet('/sitemap');
    $this->assertNoText($this->vocabulary->getDescription(), 'Vocabulary description is not included.');

    // Create taxonomy terms.
    $this->createTerms($this->vocabulary);

    // Set to show all taxonomy terms, even if they are not assigned to any
    // nodes.
    $edit = [
      'term_threshold' => -1,
    ];
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert that the vocabulary description is included in the sitemap when
    // terms are displayed.
    $this->drupalGet('/sitemap');
    $this->assertText($this->vocabulary->getDescription(), 'Vocabulary description is included.');

    // Configure module not to show vocabulary descriptions.
    $edit = [
      'show_description' => FALSE,
    ];
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert that vocabulary description is not included in the sitemap.
    $this->drupalGet('/sitemap');
    $this->assertNoText($this->vocabulary->getDescription(), 'Vocabulary description is not included.');
  }

  /**
   * Ensure that deleted vocabularies do not trigger a fatal error if ids.
   *
   * Still exist in config.
   *
   * @TODO add a hook_vocabulary_alter if that is a thing?
   */
  public function testDeletedVocabulary() {
    // Create the vocabulary to delete.
    $this->vocabularyToDelete = $this->createVocabulary();

    // Configure the sitemap to display both vocabularies.
    $vid = $this->vocabulary->id();
    $vid_to_delete = $this->vocabularyToDelete->id();
    $edit = [
      "show_vocabularies[$vid]" => $vid,
      "show_vocabularies[$vid_to_delete]" => $vid_to_delete,
    ];
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Delete the vocabulary.
    $this->vocabularyToDelete->delete();

    // Visit /sitemap.
    $this->drupalGet('/sitemap');
  }

}
