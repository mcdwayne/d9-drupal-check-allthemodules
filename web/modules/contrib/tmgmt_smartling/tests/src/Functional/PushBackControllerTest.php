<?php

namespace Drupal\Tests\tmgmt_smartling\Functional;

/**
 * Push back controller tests.
 *
 * @group tmgmt_smartling
 */
class PushBackControllerTest extends SmartlingTestBase {

  /**
   * Push back non existing job test.
   */
  public function testPushBackNonExistingJob() {
    if (!empty($this->smartlingPluginProviderSettings)) {
      $translator = $this->setUpSmartlingProviderSettings($this->smartlingPluginProviderSettings);
      $job = $this->requestTranslationForNode($this->testNodeId, $this->targetLanguage, $translator);
      $fileName = $job->getTranslatorPlugin()->getFileName($job);
      $this->checkGeneratedFile($fileName, $this->testNodeTitle);

      // Try to push back un-existing job.
      $this->drupalGet("tmgmt-smartling-callback/100500", [
        'query' => [
          'fileUri' => $fileName,
          'locale' => 'fr-FR',
        ],
      ]);
      $this->assertResponse(404);
    }
    else {
      $this->fail("Smartling settings file for simpletests not found.");
    }
  }

  /**
   * Push back existing job without parameters.
   */
  public function testPushBackExistingJobWithoutLocaleAndFileUri() {
    if (!empty($this->smartlingPluginProviderSettings)) {
      $translator = $this->setUpSmartlingProviderSettings($this->smartlingPluginProviderSettings);
      $job = $this->requestTranslationForNode($this->testNodeId, $this->targetLanguage, $translator);
      $fileName = $job->getTranslatorPlugin()->getFileName($job);
      $this->checkGeneratedFile($fileName, $this->testNodeTitle);

      // Try to push back existing job without locale and fileUri parameters.
      $this->drupalGet("tmgmt-smartling-callback/{$job->id()}");
      $this->assertResponse(404);
    }
    else {
      $this->fail("Smartling settings file for simpletests not found.");
    }
  }

  /**
   * Push back existing job without locale.
   */
  public function testPushBackExistingJobWithoutLocale() {
    if (!empty($this->smartlingPluginProviderSettings)) {
      $translator = $this->setUpSmartlingProviderSettings($this->smartlingPluginProviderSettings);
      $job = $this->requestTranslationForNode($this->testNodeId, $this->targetLanguage, $translator);
      $fileName = $job->getTranslatorPlugin()->getFileName($job);
      $this->checkGeneratedFile($fileName, $this->testNodeTitle);

      // Try to push back existing job without locale parameter.
      $this->drupalGet("tmgmt-smartling-callback/{$job->id()}", [
        'query' => [
          'fileUri' => $fileName,
        ],
      ]);
      $this->assertResponse(404);
    }
    else {
      $this->fail("Smartling settings file for simpletests not found.");
    }
  }

  /**
   * Push back existing job without file uri.
   */
  public function testPushBackExistingJobWithoutFileUri() {
    if (!empty($this->smartlingPluginProviderSettings)) {
      $translator = $this->setUpSmartlingProviderSettings($this->smartlingPluginProviderSettings);
      $job = $this->requestTranslationForNode($this->testNodeId, $this->targetLanguage, $translator);
      $fileName = $job->getTranslatorPlugin()->getFileName($job);
      $this->checkGeneratedFile($fileName, $this->testNodeTitle);

      $this->drupalGet("tmgmt-smartling-callback/{$job->id()}", [
        'query' => [
          'locale' => 'fr-FR',
        ],
      ]);
      $this->assertResponse(404);
    }
    else {
      $this->fail("Smartling settings file for simpletests not found.");
    }
  }

  /**
   * Push back existing job with parameters.
   */
  public function testPushBackExistingJobWithParameters() {
    if (!empty($this->smartlingPluginProviderSettings)) {
      $translator = $this->setUpSmartlingProviderSettings($this->smartlingPluginProviderSettings);
      $job = $this->requestTranslationForNode($this->testNodeId, $this->targetLanguage, $translator);
      $fileName = $job->getTranslatorPlugin()->getFileName($job);
      $this->checkGeneratedFile($fileName, $this->testNodeTitle);

      // Check fr node title before translation (should be same as en title).
      $this->drupalGet("$this->targetLanguage/node/$this->testNodeId");
      $this->assertResponse(200);
      $this->assertText($this->testNodeTitle);

      // Try to push back existing job.
      $this->drupalGet("tmgmt-smartling-callback/{$job->id()}", [
        'query' => [
          'fileUri' => $fileName,
          'locale' => 'fr-FR',
        ],
      ]);
      $this->assertResponse(200);

      // Check fr node title after translation (should be same as en title).
      $this->drupalGet("$this->targetLanguage/node/$this->testNodeId");
      $this->assertResponse(200);
      $this->assertNoText($this->testNodeTitle);
    }
    else {
      $this->fail("Smartling settings file for simpletests not found.");
    }
  }

  /**
   * Use callback url. Do not override host.
   */
  public function testUseCallbackUrlDoNotOverrideHost() {
    if (!empty($this->smartlingPluginProviderSettings)) {
      global $base_url;

      $providerSettings = $this->smartlingPluginProviderSettings;
      $providerSettings['settings[callback_url_use]'] = TRUE;
      $providerSettings['settings[callback_url_host]'] = '';

      $translator = $this->setUpSmartlingProviderSettings($providerSettings);
      $job = $this->requestTranslationForNode($this->testNodeId, $this->targetLanguage, $translator);
      $translatorPlugin = $job->getTranslatorPlugin();
      $callbackUrl = $this->invokeMethod($translatorPlugin, 'getCallbackUrl', [$job]);

      $this->assertTrue($base_url . '/tmgmt-smartling-callback/1', $callbackUrl);
    }
    else {
      $this->fail("Smartling settings file for simpletests not found.");
    }
  }

  /**
   * Use callback url. Override host.
   */
  public function testUseCallbackUrlOverrideHost() {
    if (!empty($this->smartlingPluginProviderSettings)) {
      global $base_url;

      $testHost = 'https://example.com';
      $providerSettings = $this->smartlingPluginProviderSettings;
      $providerSettings['settings[callback_url_use]'] = TRUE;
      $providerSettings['settings[callback_url_host]'] = $testHost;

      $translator = $this->setUpSmartlingProviderSettings($providerSettings);
      $job = $this->requestTranslationForNode($this->testNodeId, $this->targetLanguage, $translator);
      $translatorPlugin = $job->getTranslatorPlugin();
      $callbackUrl = $this->invokeMethod($translatorPlugin, 'getCallbackUrl', [$job]);

      $this->assertNotEqual($base_url, $testHost);
      $this->assertTrue($testHost . '/tmgmt-smartling-callback/1', $callbackUrl);
    }
    else {
      $this->fail("Smartling settings file for simpletests not found.");
    }
  }

}
