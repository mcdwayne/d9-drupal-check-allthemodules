<?php

namespace Drupal\Tests\tmgmt_smartling_log_settings\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests TmgmtSmartlingLogSettingsTest settings.
 *
 * @group tmgmt_smartling_log_settings
 */
class TmgmtSmartlingLogSettingsTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['tmgmt_smartling_log_settings'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $admin_user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests the tmgmt_smartling_log_settings settings page.
   */
  public function testValidSettingsNotEmptyConfig() {
    $this->drupalPostForm('admin/config/development/logging', ['tmgmt_smartling_log_settings_severity_mapping' => "smartling_api: info\r\ntmgmt_smartling: notice"], t('Save configuration'));
    $this->assertSession()->pageTextContains(t('The configuration options have been saved.'));
  }

  /**
   * Tests the tmgmt_smartling_log_settings settings page: empty config.
   */
  public function testValidSettingsEmptyConfig() {
    $this->drupalPostForm('admin/config/development/logging', ['tmgmt_smartling_log_settings_severity_mapping' => ""], t('Save configuration'));
    $this->assertSession()->pageTextContains(t('The configuration options have been saved.'));
  }

  /**
   * Tests the tmgmt_smartling_log_settings settings page: invalid yaml.
   */
  public function testSettingsInvalidYaml() {
    $this->drupalPostForm('admin/config/development/logging', ['tmgmt_smartling_log_settings_severity_mapping' => "smartling_api: info\r\n- tmgmt_smartling: notice"], t('Save configuration'));
    $this->assertSession()->pageTextContains(t('Config must be a valid yaml.'));
  }

  /**
   * Tests the tmgmt_smartling_log_settings settings page: not array.
   */
  public function testSettingsInvalidConfigNotArray() {
    $this->drupalPostForm('admin/config/development/logging', ['tmgmt_smartling_log_settings_severity_mapping' => "smartling_api info"], t('Save configuration'));
    $this->assertSession()->pageTextContains(t('Invalid config format.'));
  }

  /**
   * Tests the tmgmt_smartling_log_settings settings page: keys are not strings.
   */
  public function testSettingsInvalidConfigKeysAreNotStrings() {
    $this->drupalPostForm('admin/config/development/logging', ['tmgmt_smartling_log_settings_severity_mapping' => "- smartling_api: info\r\n- tmgmt_smartling: notice"], t('Save configuration'));
    $this->assertSession()->pageTextContains(t('Invalid config format.'));
  }

  /**
   * Tests the tmgmt_smartling_log_settings settings page: values are not strings.
   */
  public function testSettingsInvalidConfigOneOrMoreValuesAreNotValidSeverityLevel() {
    $this->drupalPostForm('admin/config/development/logging', ['tmgmt_smartling_log_settings_severity_mapping' => "smartling_api: info\r\ntmgmt_smartling: severity_level"], t('Save configuration'));
    $this->assertSession()->pageTextContains(t('Invalid config format.'));
  }

}
