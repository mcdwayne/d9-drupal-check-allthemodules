<?php

namespace Drupal\Tests\tmgmt_smartling\Functional;

/**
 * Basic flow tests.
 *
 * @group tmgmt_smartling
 */
class BaseFlowTest extends SmartlingTestBase {

  /**
   * Test upload and download translation.
   */
  public function testUploadFileAndDownloadTranslation() {
    if (!empty($this->smartlingPluginProviderSettings)) {
      $translator = $this->setUpSmartlingProviderSettings($this->smartlingPluginProviderSettings);
      $job = $this->requestTranslationForNode($this->testNodeId, $this->targetLanguage, $translator);
      $fileName = $job->getTranslatorPlugin()->getFileName($job);
      $this->checkGeneratedFile($fileName, $this->testNodeTitle);

      // Check fr node title before translation (should be same as en title).
      $this->drupalGet("$this->targetLanguage/node/$this->testNodeId");
      $this->assertResponse(200);
      $this->assertText($this->testNodeTitle);

      // Download translated file.
      $this->downloadAndCheckTranslatedFile($job->id(), $fileName);

      // Check translation.
      $this->drupalGet("$this->targetLanguage/node/$this->testNodeId");
      $this->assertResponse(200);
      $this->assertNoText($this->testNodeTitle);
    }
    else {
      $this->fail("Smartling settings file for simpletests not found.");
    }
  }

  /**
   * Test request translation when there is one provider in a list.
   *
   * There was an issue when there is only one provider in the list
   * and system requests translation without checkout settings form.
   */
  public function testRequestTranslationWhenOneProviderAvailable() {
    if (!empty($this->smartlingPluginProviderSettings)) {
      $this->drupalPostForm('/admin/tmgmt/translators/manage/smartling', $this->smartlingPluginProviderSettings, t('Save'));
      $this->drupalPostForm('/admin/tmgmt/translators/manage/local/delete', [], t('Delete'));
      $this->drupalPostForm('/admin/tmgmt/translators/manage/file/delete', [], t('Delete'));
      $this->drupalPostForm('/admin/tmgmt/translators/manage/test_translator/delete', [], t('Delete'));

      $this->drupalPostForm('/admin/tmgmt/sources', [
        'items[1]' => 1,
        'target_language' => 'de',
      ], t('Request translation'));

      $this->assertText('One job needs to be checked out.');
      $this->assertText('Create new job');
      $this->assertText('Add to job');
    }
    else {
      $this->fail("Smartling settings file for simpletests not found.");
    }
  }

}
