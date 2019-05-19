<?php

namespace Drupal\Tests\tmgmt_smartling\Kernel;

use Smartling\AuditLog\Params\CreateRecordParameters;

/**
 * Tests file download flow.
 *
 * @group tmgmt_smartling
 */
class DownloadFlowTest extends SmartlingTestBase {

  /**
   * Download success full flow.
   */
  public function testDownloadSuccessFullFlow() {
    $translate_job = $this->createJobWithItems([
      'batch_uid' => 'uid',
      'batch_execute_on_job' => 1,
    ]);

    $this->translationRequestManagerMock->expects($this->never())
      ->method('commitError');

    $this->translationRequestManagerMock->expects($this->once())
      ->method('getTranslationRequest')
      ->with($translate_job)
      ->willReturn(['translationRequestUid' => 'test']);

    $this->apiWrapperMock->expects($this->once())
      ->method('getApi')
      ->with('file')
      ->willReturn($this->fileApiMock);

    $this->apiWrapperMock->expects($this->once())
      ->method('createAuditLogRecord')
      ->with(
        $translate_job,
        NULL,
        \Drupal::currentUser(),
        CreateRecordParameters::ACTION_TYPE_DOWNLOAD
      );

    $this->fileApiMock->expects($this->once())
      ->method('downloadFile')
      ->with(
        'JobID1_en_de.xml',
        'de',
        $this->callback(function($subject) {
          $params = $subject->exportToArray();

          return $params['retrievalType'] == 'published';
        })
      )
      ->willReturn('xml');

    $this->pluginMock->expects($this->once())
      ->method('validateImport')
      ->with(
        'public://tmgmt_smartling_translations/JobID1_en_de.xml',
        $translate_job
      )
      ->willReturn(TRUE);

    $this->pluginMock->expects($this->once())
      ->method('import')
      ->with(
        'public://tmgmt_smartling_translations/JobID1_en_de.xml',
        $translate_job
      )
      ->willReturn([]);

    $this->translationRequestManagerMock->expects($this->once())
      ->method('commitSuccessfulDownload')
      ->with($translate_job)
      ->willReturn(FALSE);

    $this->apiWrapperMock->expects($this->at(2))
      ->method('createFirebaseRecord')
      ->with('tmgmt_smartling', 'notifications', 10, [
        "message" => 'Translation for "public://tmgmt_smartling_translations/JobID1_en_de.xml" (job id = 1) was successfully downloaded and imported.',
        "type" => "status",
      ]);

    $this->apiWrapperMock->expects($this->at(3))
      ->method('createFirebaseRecord')
      ->with('tmgmt_smartling', 'notifications', 10, [
        "message" => 'Can\'t update update state and exported date for translation request. See logs for more info.',
        "type" => "warning",
      ]);

    tmgmt_smartling_download_file($translate_job);
  }

  /**
   * Download fail flow: get translation request failed.
   */
  public function testDownloadFailFlowGetTranslationRequestFailed() {
    $translate_job = $this->createJobWithItems([
      'batch_uid' => 'uid',
      'batch_execute_on_job' => 1,
    ]);

    $this->translationRequestManagerMock->expects($this->never())
      ->method('commitError');

    $this->translationRequestManagerMock->expects($this->once())
      ->method('getTranslationRequest')
      ->with($translate_job)
      ->willReturn(FALSE);

    $this->apiWrapperMock->expects($this->never())
      ->method('getApi');

    $this->fileApiMock->expects($this->never())
      ->method('downloadFile');

    $this->translationRequestManagerMock->expects($this->never())
      ->method('commitSuccessfulDownload');

    $this->pluginMock->expects($this->never())
      ->method('validateImport');

    $this->pluginMock->expects($this->never())
      ->method('import');

    $this->apiWrapperMock->expects($this->once())
      ->method('createFirebaseRecord')
      ->with('tmgmt_smartling', 'notifications', 10, [
        "message" => 'File JobID1_en_de.xml (job id = 1) wasn\'t downloaded: can\'t find related translation request. See logs for more info.',
        "type" => "error",
      ]);

    tmgmt_smartling_download_file($translate_job);
  }

  /**
   * Download success partial flow: import skipped.
   */
  public function testDownloadSuccessPartialFlowImportSkipped() {
    $translate_job = $this->createJobWithItems([
      'batch_uid' => 'uid',
      'batch_execute_on_job' => 1,
    ]);
    $translate_job->set('job_file_content_hash', '0f635d0e0f3874fff8b581c132e6c7a7');

    // Remove job items in order to not to force import. We need to avoid
    // Drupal::entityTypeManager mocking. See tmgmt_smartling_download_file,
    // line 94.
    foreach ($translate_job->getItems() as $item) {
      $item->delete();
    }

    $translate_job->save();

    $this->translationRequestManagerMock->expects($this->never())
      ->method('commitError');

    $this->translationRequestManagerMock->expects($this->once())
      ->method('getTranslationRequest')
      ->willReturn(['translationRequestUid' => 'test']);

    $this->apiWrapperMock->expects($this->once())
      ->method('getApi')
      ->with('file')
      ->willReturn($this->fileApiMock);

    $this->apiWrapperMock->expects($this->once())
      ->method('createAuditLogRecord')
      ->with(
        $translate_job,
        NULL,
        \Drupal::currentUser(),
        CreateRecordParameters::ACTION_TYPE_DOWNLOAD
      );

    $this->fileApiMock->expects($this->once())
      ->method('downloadFile')
      ->with(
        'JobID1_en_de.xml',
        'de',
        $this->callback(function($subject) {
          $params = $subject->exportToArray();

          return $params['retrievalType'] == 'published';
        })
      )
      ->willReturn('xml');

    $this->translationRequestManagerMock->expects($this->once())
      ->method('commitSuccessfulDownload')
      ->with($translate_job)
      ->willReturn(TRUE);

    $this->pluginMock->expects($this->once())
      ->method('validateImport')
      ->with(
        'public://tmgmt_smartling_translations/JobID1_en_de.xml',
        $translate_job
      )
      ->willReturn(TRUE);

    $this->pluginMock->expects($this->never())
      ->method('import');

    $this->apiWrapperMock->expects($this->once())
      ->method('createFirebaseRecord')
      ->with('tmgmt_smartling', 'notifications', 10, [
        "message" => 'Translation for "public://tmgmt_smartling_translations/JobID1_en_de.xml" (job id = 1) was successfully downloaded but import was skipped: downloaded and existing translations are equal.',
        "type" => "warning",
      ]);

    tmgmt_smartling_download_file($translate_job);
  }

  /**
   * Download fail flow.
   */
  public function testDownloadFailFlow() {
    $exception = new \Exception("Test");
    $translation_request = ['translationRequestUid' => 'test'];
    $translate_job = $this->createJobWithItems([
      'batch_uid' => 'uid',
      'batch_execute_on_job' => 1,
    ]);

    $this->translationRequestManagerMock->expects($this->once())
      ->method('getTranslationRequest')
      ->with($translate_job)
      ->willReturn($translation_request);

    $this->apiWrapperMock->expects($this->once())
      ->method('getApi')
      ->with('file')
      ->willReturn($this->fileApiMock);

    $this->apiWrapperMock->expects($this->once())
      ->method('createAuditLogRecord')
      ->with(
        $translate_job,
        NULL,
        \Drupal::currentUser(),
        CreateRecordParameters::ACTION_TYPE_DOWNLOAD
      );

    $this->fileApiMock->expects($this->once())
      ->method('downloadFile')
      ->with(
        'JobID1_en_de.xml',
        'de',
        $this->callback(function($subject) {
          $params = $subject->exportToArray();

          return $params['retrievalType'] == 'published';
        })
      )
      ->will($this->throwException($exception));

    $this->translationRequestManagerMock->expects($this->once())
      ->method('commitError')
      ->with($translate_job, $translation_request, $exception);

    $this->translationRequestManagerMock->expects($this->never())
      ->method('commitSuccessfulDownload');

    $this->pluginMock->expects($this->never())
      ->method('validateImport');

    $this->pluginMock->expects($this->never())
      ->method('import');

    $this->apiWrapperMock->expects($this->once())
      ->method('createFirebaseRecord')
      ->with('tmgmt_smartling', 'notifications', 10, [
        "message" => 'File JobID1_en_de.xml (job id = 1) wasn\'t downloaded. Please see logs for more info.',
        "type" => "error",
      ]);

    tmgmt_smartling_download_file($translate_job);
  }

  /**
   * Download fail flow: validation failed.
   */
  public function testDownloadFailFlowValidationFailed() {
    $translate_job = $this->createJobWithItems([
      'batch_uid' => 'uid',
      'batch_execute_on_job' => 1,
    ]);

    $this->translationRequestManagerMock->expects($this->never())
      ->method('commitError');

    $this->translationRequestManagerMock->expects($this->once())
      ->method('getTranslationRequest')
      ->with($translate_job)
      ->willReturn(['translationRequestUid' => 'test']);

    $this->apiWrapperMock->expects($this->once())
      ->method('getApi')
      ->with('file')
      ->willReturn($this->fileApiMock);

    $this->apiWrapperMock->expects($this->once())
      ->method('createAuditLogRecord')
      ->with(
        $translate_job,
        NULL,
        \Drupal::currentUser(),
        CreateRecordParameters::ACTION_TYPE_DOWNLOAD
      );

    $this->fileApiMock->expects($this->once())
      ->method('downloadFile')
      ->with(
        'JobID1_en_de.xml',
        'de',
        $this->callback(function($subject) {
          $params = $subject->exportToArray();

          return $params['retrievalType'] == 'published';
        })
      )
      ->willReturn('xml');

    $this->pluginMock->expects($this->once())
      ->method('validateImport')
      ->with(
        'public://tmgmt_smartling_translations/JobID1_en_de.xml',
        $translate_job
      )
      ->willReturn(FALSE);

    $this->pluginMock->expects($this->never())
      ->method('import');

    $this->translationRequestManagerMock->expects($this->never())
      ->method('commitSuccessfulDownload');

    $this->apiWrapperMock->expects($this->once())
      ->method('createFirebaseRecord')
      ->with('tmgmt_smartling', 'notifications', 10, [
        "message" => 'Translation for "public://tmgmt_smartling_translations/JobID1_en_de.xml" (job id = 1) was successfully downloaded but validation failed. See logs for more info.',
        "type" => "error",
      ]);

    tmgmt_smartling_download_file($translate_job);
  }

  /**
   * Download fail flow: import failed.
   */
  public function testDownloadFailFlowImportFailed() {
    $exception = new \Exception("Test");
    $translation_request = ['translationRequestUid' => 'test'];
    $translate_job = $this->createJobWithItems([
      'batch_uid' => 'uid',
      'batch_execute_on_job' => 1,
    ]);

    $this->translationRequestManagerMock->expects($this->once())
      ->method('getTranslationRequest')
      ->with($translate_job)
      ->willReturn($translation_request);

    $this->apiWrapperMock->expects($this->once())
      ->method('getApi')
      ->with('file')
      ->willReturn($this->fileApiMock);

    $this->apiWrapperMock->expects($this->once())
      ->method('createAuditLogRecord')
      ->with(
        $translate_job,
        NULL,
        \Drupal::currentUser(),
        CreateRecordParameters::ACTION_TYPE_DOWNLOAD
      );

    $this->fileApiMock->expects($this->once())
      ->method('downloadFile')
      ->with(
        'JobID1_en_de.xml',
        'de',
        $this->callback(function($subject) {
          $params = $subject->exportToArray();

          return $params['retrievalType'] == 'published';
        })
      )
      ->willReturn('xml');

    $this->pluginMock->expects($this->once())
      ->method('validateImport')
      ->with(
        'public://tmgmt_smartling_translations/JobID1_en_de.xml',
        $translate_job
      )
      ->willReturn(TRUE);

    $this->pluginMock->expects($this->once())
      ->method('import')
      ->with(
        'public://tmgmt_smartling_translations/JobID1_en_de.xml',
        $translate_job
      )
      ->will($this->throwException($exception));

    $this->translationRequestManagerMock->expects($this->never())
      ->method('commitSuccessfulDownload');

    $this->translationRequestManagerMock->expects($this->once())
      ->method('commitError')
      ->with($translate_job, $translation_request, $exception);

    $this->apiWrapperMock->expects($this->once())
      ->method('createFirebaseRecord')
      ->with('tmgmt_smartling', 'notifications', 10, [
        "message" => 'Translation for "public://tmgmt_smartling_translations/JobID1_en_de.xml" (job id = 1) was successfully downloaded but import failed. See logs for more info.',
        "type" => "error",
      ]);

    tmgmt_smartling_download_file($translate_job);
  }
}
