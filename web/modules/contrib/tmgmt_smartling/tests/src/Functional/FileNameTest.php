<?php

namespace Drupal\Tests\tmgmt_smartling\Functional;

/**
 * File name tests.
 *
 * @group tmgmt_smartling
 */
class FileNameTest extends SmartlingTestBase {

  /**
   * Test different file names for different jobs.
   */
  public function testTwoJobsDifferentFileName() {
    $translator = $this->setUpSmartlingProviderSettings($this->smartlingPluginProviderSettings);
    $job = $this->requestTranslationForNode($this->testNodeId, $this->targetLanguage, $translator);
    $fileName = $job->getTranslatorPlugin()->getFileName($job);
    $this->assertEqual($fileName, 'JobID1_en_fr.xml');

    $newJob = $this->requestTranslationForNode($this->testNodeId, 'de', $translator);
    $newFileName = $newJob->getTranslatorPlugin()->getFileName($newJob);
    $this->assertEqual($newFileName, 'JobID2_en_de.xml');
    $this->assertNotEqual($job->id(), $newJob->id());
    $this->assertNotEqual($fileName, $newFileName);
  }

  /**
   * Test not altered file name.
   */
  public function testOriginalFilename() {
    $translator = $this->setUpSmartlingProviderSettings($this->smartlingPluginProviderSettings);
    $job = $this->requestTranslationForNode($this->testNodeId, $this->targetLanguage, $translator);
    $fileName = $job->getTranslatorPlugin()->getFileName($job);
    $this->assertEqual($fileName, 'JobID1_en_fr.xml');
  }

  /**
   * Test altered file name.
   */
  public function testAlteredFilename() {
    \Drupal::service('module_installer')->install(['tmgmt_smartling_test_alter_filename']);

    $translator = $this->setUpSmartlingProviderSettings($this->smartlingPluginProviderSettings);
    $job = $this->requestTranslationForNode($this->testNodeId, $this->targetLanguage, $translator);
    $fileName = $job->getTranslatorPlugin()->getFileName($job);
    $this->assertNotEqual($fileName, 'JobID1_en_fr.xml');
    $this->assertEqual($fileName, 'TEST_job_id_1.xml');
  }

}
