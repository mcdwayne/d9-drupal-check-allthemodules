<?php

namespace Drupal\Tests\admin_user_language\Functional;

/**
 * Tests BasicForm to alter admin_user_language configuration.
 *
 * @coversDefaultClass \Drupal\admin_user_language\Form\BasicForm
 *
 * @group admin_user_language
 */
class AdminUserLanguageBaseFormTest extends AdminUserLanguageBrowserTestBase {

  /**
   * Tests the basic functionality of the field.
   */
  public function testBasicFormSettings() {
    $activeLanguages = $this->getActiveLanguages();

    // Testing the base configuration before any action.
    $config = \Drupal::service('config.factory')
                     ->get('admin_user_language.settings');

    self::assertEquals(-1, $config->get('default_language_to_assign'));
    self::assertEquals(FALSE, $config->get('prevent_user_override'));

    // Display settings form.
    $this->drupalGet('admin/config/admin_user_language/settings');

    $this->assertSession()->fieldExists('default_language_to_assign'); // Language chooser element found.
    $this->assertSession()->fieldExists('prevent_user_override'); // Force language element found

    // Filling the fields with some real data by picking a random active language.
    $randomLanguage = array_rand($activeLanguages);

    $edit = [
      'default_language_to_assign' => $randomLanguage,
      'prevent_user_override'      => TRUE,
    ];

    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $this->assertSession()
         ->responseContains(t('The configuration options have been saved.')); // Configuration saved with the random language . $randomLanguage .

    // Checking the data that we just saved.
    $config = \Drupal::service('config.factory')
                     ->get('admin_user_language.settings');
    self::assertEquals($randomLanguage, $config->get('default_language_to_assign'));
    self::assertEquals(TRUE, $config->get('prevent_user_override'));

    // Trying to save the default settings.
    $this->drupalGet('admin/config/admin_user_language/settings');

    $this->assertSession()->fieldValueEquals('default_language_to_assign', $randomLanguage); // Language chooser element has the correct value.
    $this->assertSession()->fieldValueEquals('prevent_user_override', TRUE); // Force language element has the correct value.

    $edit = array(
      'default_language_to_assign' => -1,
      'prevent_user_override' => FALSE,
    );

    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $this->assertSession()
         ->responseContains(t('The configuration options have been saved.')); // Configuration saved with the random language -1.

    // Checking the data that we just saved.
    $config = \Drupal::service('config.factory')
                     ->get('admin_user_language.settings');
    self::assertEquals(-1, $config->get('default_language_to_assign'));
    self::assertEquals(FALSE, $config->get('prevent_user_override'));
  }

}
