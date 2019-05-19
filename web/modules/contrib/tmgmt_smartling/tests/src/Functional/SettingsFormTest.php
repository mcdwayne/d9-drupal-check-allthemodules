<?php

namespace Drupal\Tests\tmgmt_smartling\Functional;

/**
 * Settings form tests.
 *
 * @group tmgmt_smartling
 */
class SettingsFormTest extends SmartlingTestBase {

  /**
   * Test Smartling provider plugin form validation with wrong parameters.
   */
  public function testValidationWrongParameters() {
    if (!empty($this->smartlingPluginProviderSettings)) {
      // Validation with wrong parameters.
      $settings = $this->smartlingPluginProviderSettings;
      $settings['settings[project_id]'] = $this->randomString();
      $settings['settings[user_id]'] = $this->randomString();
      $settings['settings[token_secret]'] = $this->randomString();
      $settings['settings[key]'] = $this->randomString();

      $translator = $this->setUpSmartlingProviderSettings($settings);
      $supported_remote_languages = $translator->getPlugin()->getSupportedRemoteLanguages($translator);

      $this->assertEqual(0, count($supported_remote_languages));
    }
    else {
      $this->fail("Smartling settings file for simpletests not found.");
    }
  }

  /**
   * Test Smartling provider plugin form validation with correct parameters.
   */
  public function testValidationCorrectParameters() {
    if (!empty($this->smartlingPluginProviderSettings)) {
      // Validation with correct parameters.
      $translator = $this->setUpSmartlingProviderSettings($this->smartlingPluginProviderSettings);
      $supported_remote_languages = $translator->getPlugin()->getSupportedRemoteLanguages($translator);
      $this->assertNotEqual(0, count($supported_remote_languages));
    }
    else {
      $this->fail("Smartling settings file for simpletests not found.");
    }
  }

}
