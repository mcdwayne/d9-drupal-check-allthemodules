<?php
/**
 * @file
 * Tests for the pathauto_i18n node module.
 */

namespace Drupal\pathauto_i18n\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test functionality for nodes when language selected.
 */
abstract class Pathautoi18nTestBase extends WebTestBase {
  /**
   * Fully loaded user object of an admin user that has required access rights.
   *
   * @var object
   */
  protected $admin;

  /**
   * Default language.
   */
  protected $contentLanguage = 'en';

  /**
   * Available languages.
   */
  protected $availableLanguages = ['en', 'fr', 'de', 'uk'];

  /**
   * Title.
   */
  protected $title = 'pathautoi18n';

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['pathauto_i18n'];

  /**
   * Prepare test.
   */
  public function prepareTest() {
    $this->admin = $this->drupalCreateUser([
      'administer modules',
      'administer site configuration',
      'administer languages',
      'access administration pages',
      'create url aliases',
      'administer pathauto',
      'administer url aliases',
    ]);
    $this->drupalLogin($this->admin);

    foreach ($this->availableLanguages as $language) {
      if ($language != $this->contentLanguage) {
        $this->drupalPostForm('admin/config/regional/language/add', ['predefined_langcode' => $language], t('Add language'));
      }
    }

    // @todo Enable multilingual support for content type.
    // $this->drupalPost('admin/structure/types/manage/article', array('language_content_type' => 1), t('Save content type'));
  }

  /**
   * Set settings to test cleanstring.
   */
  public function setCleanStringSettings() {
    $data = array();
    foreach ($this->availableLanguages as $language) {
      $data['pathauto_ignore_words_' . $language . '_language'] = $language;
    }

    $this->drupalPost('admin/config/search/path/settings', $data, t('Save configuration'));
  }

  /**
   * Return suffix for certain language.
   */
  public function getCleanStringSuffix($skip_language) {
    $suffix = array();
    foreach ($this->availableLanguages as $language) {
      if ($language != $skip_language) {
        $suffix[] = $language;
      }
    }

    return implode('-', $suffix);
  }
}
