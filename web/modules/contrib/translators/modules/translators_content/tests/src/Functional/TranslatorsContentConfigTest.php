<?php

namespace Drupal\Tests\translators_content\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Class TranslatorsContentConfigTest.
 *
 * @package Drupal\Tests\translators_content\Functional
 *
 * @group translators_content
 */
class TranslatorsContentConfigTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public $profile = 'standard';
  /**
   * {@inheritdoc}
   */
  public static $modules = ['translators'];
  /**
   * Configs list.
   *
   * @var array
   */
  protected $configs = [
    'enable_translators_content_permissions',
    'enable_filter_translation_overview_to_skills',
    'enable_missing_skills_warning',
    'enable_auto_preset_source_language_by_skills',
    'enable_access_by_source_skills',
  ];
  /**
   * List of configs enabled by default.
   *
   * @var array
   */
  protected $configsDefault = [
    'enable_filter_translation_overview_to_skills',
    'enable_missing_skills_warning',
    'enable_auto_preset_source_language_by_skills',
  ];

  /**
   * Test that the config options is added when the module gets enabled.
   */
  public function testConfigOptions() {
    $this->drupalLogin($this->rootUser);

    foreach ($this->configs as $config_name) {
      // Check for non-existing options, since module is not enabled yet.
      $this->drupalGet('/admin/config/regional/translators');
      $this->assertSession()->statusCodeEquals(200);
      $this->assertSession()
        ->elementNotExists('css', "input[name=\"{$config_name}\"]");
    }

    // Install module.
    $this->container
      ->get('module_installer')
      ->install(['translators_content']);

    foreach ($this->configs as $config_name) {
      // Check for existing options, since module is enabled now.
      $this->drupalGet('/admin/config/regional/translators');
      $this->assertSession()->statusCodeEquals(200);
      $this->assertSession()
        ->elementExists('css', "input[name=\"{$config_name}\"]");
    }
  }

  /**
   * Test that the config options changes.
   */
  public function testConfigOptionsChanges() {
    $this->drupalLogin($this->rootUser);
    // Install module.
    $this->container
      ->get('module_installer')
      ->install(['translators_content']);

    foreach ($this->configs as $config_name) {
      // Check the default/initial value of this option.
      $option_value = \Drupal::configFactory()
        ->getEditable('translators.settings')
        ->get($config_name);
      if (!in_array($config_name, $this->configsDefault)) {
        $this->assertFalse($option_value);
      }
      else {
        $this->assertTrue($option_value);
      }

      // Update value.
      $this->drupalPostForm(
        '/admin/config/regional/translators',
        [$config_name => TRUE],
        'Save configuration'
      );

      $this->assertSession()->statusCodeEquals(200);
      $this->assertTextHelper('The configuration options have been saved.', FALSE);
      $this->assertSession()->fieldValueEquals($config_name, '1');
      // Clean up the static caches of configuration.
      \Drupal::configFactory()->clearStaticCache();

      // Check the updated value of this option.
      $option_value = \Drupal::configFactory()
        ->getEditable('translators.settings')
        ->get($config_name);
      $this->assertTrue($option_value);
    }
  }

}
