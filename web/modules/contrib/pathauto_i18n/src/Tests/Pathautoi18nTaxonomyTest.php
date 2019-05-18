<?php
/**
 * @file
 * Tests for the pathauto_i18n taxonomy module.
 */

namespace Drupal\pathauto_i18n\Tests;

use Drupal\pathauto_i18n\Tests;

define('PATHAUTO_I18N_TAG_VOCABULARU_MACHINE_NAME', 'tags');

/**
 * Pathauto i18n test functionality for taxonomy.
 *
 * @group pathauto_i18n
 */
class Pathautoi18nTaxonomyTest extends Pathautoi18nTestBase {
  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['pathauto_i18n_taxonomy'];

  /**
   * SetUp method.
   */
  public function setUp() {
    $this->prepareTest();

    // Configure patterns for all language for easy testing.
    /*$edit = array(
      'pathauto_taxonomy_term_pattern' => 'all/[term:name]',
      'pathauto_taxonomy_term_tags_pattern' => 'neutral/[term:name]',
    );

    foreach ($this->availableLanguages as $language) {
      $edit['pathauto_taxonomy_term_tags_' . $language . '_pattern'] = $language . '/[term:name]';
    }
    $this->drupalPost('admin/config/search/path/patterns', $edit, t('Save configuration'));*/
  }

  /**
   * Test taxonomy terms with automatic alias.
   */
  public function testAutomaticAliasTaxonomy() {
    /*$this->createTaxonomyTerm(TRUE, TRUE);
    // Check aliases.
    $this->drupalGet('admin/config/search/path');
    foreach ($this->availableLanguages as $language) {
      $alias = $language . '/' . $this->title;
      $this->assertText($alias, 0, "Exist alias '$alias' for language '$language'.");
    }*/
  }

  /**
   * Test taxonomy terms with custom alias for certain language.
   */
  public function testCustomAliasTaxonomy() {
    /*$custom_alias = 'custom/' . $this->title;
    $neutral_alias = 'neutral/' . $this->title;
    $this->createTaxonomyTerm(TRUE, TRUE, $custom_alias);
    // Check aliases and custom alias.
    $this->drupalGet('admin/config/search/path');
    foreach ($this->availableLanguages as $language) {
      $alias = $language . '/' . $this->title;
      $this->assertText($alias, 0, "Exist alias '$alias' for language '$language'.");
    }

    $this->assertText($custom_alias, 0, "Exist custom alias '$custom_alias'.");
    $this->assertNoText($neutral_alias, 0, "Alias '$neutral_alias' for undefined language not exist.");*/
  }

  /**
   * Test clearing of string.
   */
  public function testCleanString() {
    /*// Set appropriate title which will allow us remove parts of path.
    $initial_title = $this->title;
    $this->title .= ' ' . implode(' ', $this->availableLanguages);

    $this->setCleanStringSettings();
    $this->createTaxonomyTerm(TRUE, TRUE);

    // Check aliases.
    $this->drupalGet('admin/config/search/path');
    foreach ($this->availableLanguages as $language) {
      $suffix = $this->getCleanStringSuffix($language);
      $alias = $language . '/' . $initial_title . '/' . $suffix;
      $this->assertNoText($alias, 0, "Exist alias '$alias' for language '$language' with excluded string '$language'.");
    }*/
  }

  /**
   * Helper to create taxonomy term.
   */
  public function createTaxonomyTerm($pathauto_i18n_status, $pathauto, $alias = FALSE) {
    $vocabulary = taxonomy_vocabulary_machine_name_load(PATHAUTO_I18N_TAG_VOCABULARU_MACHINE_NAME);
    $term = new stdClass();
    $term->name = $this->title;
    $term->description = $this->title;
    // Use the first available text format.
    $term->format = db_query_range('SELECT format FROM {filter_format}', 0, 1)->fetchField();
    $term->vid = $vocabulary->vid;
    $term->path = array(
      'pathauto_i18n_status' => $pathauto_i18n_status,
      'pathauto' => $pathauto,
    );
    if (!empty($alias)) {
      $term->path['alias'] = $alias;
    }
    taxonomy_term_save($term);
  }
}
