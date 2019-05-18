<?php

namespace Drupal\Tests\admin_user_language\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Class AdminUserLanguageBrowserTestBase
 *
 * Provides shared methods and functionality.
 *
 * @package Drupal\admin_user_language\Tests
 */
abstract class AdminUserLanguageBrowserTestBase extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['user', 'admin_user_language'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $admin_user = $this->drupalCreateUser([
      'access administration pages',
      'administer admin interface language'
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Gets the active languages.
   *
   * @return array
   */
  protected function getActiveLanguages() {
    $languages = (array) \Drupal::service('language_manager')->getLanguages();
    $displayLanguages = [];
    /** @var \Drupal\Core\Language\Language $lang */
    foreach ($languages as $lang) {
      $displayLanguages[$lang->getId()] = $lang->getName();
    }
    return $displayLanguages;
  }

}
