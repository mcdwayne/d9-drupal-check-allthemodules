<?php
/**
 * @file
 * Tests for the pathauto_i18n user module.
 */

namespace Drupal\pathauto_i18n\Tests;

use Drupal\pathauto_i18n\Tests;

/**
 * Pathauto i18n test functionality for user.
 *
 * @group pathauto_i18n
 */
class Pathautoi18nUserTest extends Pathautoi18nTestBase {
  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['pathauto_i18n_user'];

  /**
   * SetUp method.
   */
  public function setUp() {
    $modules[] = 'pathauto_i18n_user';
    $this->prepareTest();

    /*// Configure patterns for all language for easy testing.
    $edit = array(
      'pathauto_user_pattern' => 'neutral/users/[user:name]',
    );

    foreach ($this->availableLanguages as $language) {
      $edit['pathauto_user_user_' . $language . '_pattern'] = $language . '/users/[user:name]';
    }
    $this->drupalPost('admin/config/search/path/patterns', $edit, t('Save configuration'));*/
  }

  /**
   * Test user alias.
   */
  public function testUserAlias() {
    /*drupal_static_reset('pathauto_pattern_load_by_entity');
    $this->createUser();
    // Check aliases.
    $this->drupalGet('admin/config/search/path/list/users');
    foreach ($this->availableLanguages as $language) {
      $alias = $language . '/users/' . $this->title;
      $this->assertText($alias, 0, "Exist alias '$alias' for language '$language'.");
    }*/
  }

  /**
   * Test clearing of string.
   */
  public function testCleanString() {
    // Set appropriate title which will allow us remove parts of path.
    /*$initial_title = $this->title;
    $this->title .= ' ' . implode(' ', $this->availableLanguages);

    $this->setCleanStringSettings();
    $this->createUser();

    // Check aliases.
    $this->drupalGet('admin/config/search/path');
    foreach ($this->availableLanguages as $language) {
      $suffix = $this->getCleanStringSuffix($language);
      $alias = $language . '/' . $initial_title . '/' . $suffix;
      $this->assertNoText($alias, 0, "Exist alias '$alias' for language '$language' with excluded string '$language'.");
    }*/
  }

  /**
   * Helper to create users.
   */
  public function createUser() {
    $edit = array();
    $edit['name'] = $this->title;
    $edit['mail'] = $edit['name'] . '@sanchiz.net';
    $edit['pass'] = user_password();
    $edit['status'] = 1;
    $edit['pathauto_i18n_status'] = 1;
    user_save(drupal_anonymous_user(), $edit);
  }
}
