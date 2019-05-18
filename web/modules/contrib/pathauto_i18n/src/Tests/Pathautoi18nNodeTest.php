<?php
/**
 * @file
 * Tests for the pathauto_i18n node module.
 */

namespace Drupal\pathauto_i18n\Tests;

use Drupal\pathauto_i18n\Tests;

/**
 * Pathauto i18n test functionality for nodes.
 *
 * @group pathauto_i18n
 */
class Pathautoi18nNodeTest extends Pathautoi18nTestBase {
  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['pathauto_i18n_node'];

  /**
   * SetUp method.
   */
  public function setUp() {
    parent::setUp();
    $this->prepareTest();

    /*// Configure patterns for all language for easy testing.
    $edit = array(
      'pathauto_node_article_pattern' => 'all/[node:title]',
      'pathauto_node_article_und_pattern' => 'neutral/[node:title]',
    );

    foreach ($this->availableLanguages as $language) {
      $edit['pathauto_node_article_' . $language . '_pattern'] = $language . '/[node:title]';
    }
    $this->drupalPost('admin/config/search/path/patterns', $edit, t('Save configuration'));*/
  }

  /**
   * Test nodes with automatic alias for certain language.
   */
  public function testAutomaticAliasLanguageSelected() {
    /*$this->createNode($this->contentLanguage, TRUE, TRUE);
    // Check aliases.
    $this->drupalGet('admin/config/search/path');
    foreach ($this->availableLanguages as $language) {
      $alias = $language . '/' . $this->title;
      $this->assertText($alias, 0, "Exist alias '$alias' for language '$language'.");
    }*/
  }

  /**
   * Test nodes with custom alias for certain language.
   */
  public function testCustomAliasLanguageSelected() {
    /*$custom_alias = 'custom/' . $this->title;
    $this->createNode($this->contentLanguage, TRUE, FALSE, $custom_alias);
    // Check aliases and custom alias.
    $this->drupalGet('admin/config/search/path');
    foreach ($this->availableLanguages as $language) {
      if ($language == 'en') {
        $alias = $language . '/' . $this->title;
        $this->assertText($custom_alias, 0, "Exist custom alias '$custom_alias'.");
        $this->assertNoText($alias, 0, "Alias '$alias' for language '$language' not exist.");
      }
      else {
        $alias = $language . '/' . $this->title;
        $this->assertText($alias, 0, "Exist alias '$alias' for language '$language'.");
      }
    }*/
  }

  /**
   * Test nodes with automatic alias for undefined language.
   */
  public function testAutomaticAliasUndefinedLanguage() {
    /*$this->contentLanguage = LANGUAGE_NONE;
    $this->testAutomaticAliasLanguageSelected();
    $neutral_alias = 'neutral/' . $this->title;
    $this->assertNoText($neutral_alias, 0, "Alias '$neutral_alias' for undefined language not exist.");*/
  }

  /**
   * Test nodes with custom alias for undefined language.
   */
  public function testCustomAliasUndefinedLanguage() {
    /*$custom_alias = 'custom/' . $this->title;
    $neutral_alias = 'neutral/' . $this->title;
    $this->createNode(LANGUAGE_NONE, TRUE, FALSE, $custom_alias);

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
    $this->createNode($this->contentLanguage, TRUE, TRUE);

    // Check aliases.
    $this->drupalGet('admin/config/search/path');
    foreach ($this->availableLanguages as $language) {
      $suffix = $this->getCleanStringSuffix($language);
      $alias = $language . '/' . $initial_title . '/' . $suffix;
      $this->assertNoText($alias, 0, "Exist alias '$alias' for language '$language' with excluded string '$language'.");
    }*/
  }

  /**
   * Helper to create node.
   */
  public function createNode($language, $pathauto_i18n_status, $pathauto, $alias = FALSE) {
    $settings = array(
      'type' => 'article',
      'title' => $this->title,
      'body' => array(
        $language => array(
          array(
            $this->randomName(64),
          ),
        ),
      ),
      'language' => $language,
      'path' => array(
        'pathauto_i18n_status' => $pathauto_i18n_status,
        'pathauto' => $pathauto,
      ),
    );
    if (!empty($alias)) {
      $settings['path']['alias'] = $alias;
    }
    $node = $this->drupalCreateNode($settings);
  }
}
