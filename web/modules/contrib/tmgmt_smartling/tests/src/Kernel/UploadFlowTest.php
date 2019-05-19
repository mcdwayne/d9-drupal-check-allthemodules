<?php

namespace Drupal\Tests\tmgmt_smartling\Kernel;

use Smartling\AuditLog\Params\CreateRecordParameters;

/**
 * Tests file upload flow.
 *
 * @group tmgmt_smartling
 */
class UploadFlowTest extends SmartlingTestBase {

  /**
   * Upload success flow with batch execution.
   */
  public function testRequestTranslationSuccessFlowExecute() {
    $translate_job = $this->createJobWithItems([
      'batch_uid' => 'uid',
      'batch_execute_on_job' => 1,
    ]);

    $this->translationRequestManagerMock->expects($this->once())
      ->method('upsertTranslationRequest')
      ->with($translate_job)
      ->willReturn(['translationRequestUid' => 'test']);

    $this->translationRequestManagerMock->expects($this->never())
      ->method('commitError');

    $this->apiWrapperMock->expects($this->once())
      ->method('getApi')
      ->with('batch')
      ->willReturn($this->batchApiMock);

    $this->apiWrapperMock->expects($this->once())
      ->method('createAuditLogRecord')
      ->with(
        $translate_job,
        NULL,
        \Drupal::currentUser(),
        CreateRecordParameters::ACTION_TYPE_UPLOAD
      );

    $this->batchApiMock->expects($this->once())
      ->method('uploadBatchFile')
      ->with(
        $this->callback(function($subject) {
          return strstr($subject, '/files/tmgmt_sources/JobID1_en_de.xml') !== FALSE;
        }),
        'JobID1_en_de.xml',
        'xml',
        'uid',
        $this->callback(function($subject) {
          $params = $subject->exportToArray();

          return $params['authorize'] == 0 &&
            preg_match('/^{"client":"drupal-tmgmt-connector","version":"(\d+\.x-\d+\.\d+|\d+\.x-\d+\.x-dev|unknown)"}$/', $params['smartling.client_lib_id']) &&
            $params['localeIdsToAuthorize'][0] == 'de' &&
            $params['smartling.translate_paths'] == 'html/body/div/div, html/body/div/span' &&
            $params['smartling.string_format_paths'] == 'html : html/body/div/div, @default : html/body/div/span' &&
            $params['smartling.variants_enabled'] == TRUE &&
            $params['smartling.source_key_paths'] == 'html/body/div/{div.sl-variant}, html/body/div/{span.sl-variant}' &&
            $params['smartling.character_limit_paths'] == 'html/body/div/limit' &&
            $params['smartling.placeholder_format_custom'] == '(@|%|!)[\w-]+';
        })
      );

    $this->apiWrapperMock->expects($this->once())
      ->method('executeBatch')
      ->with('uid');

    $this->translationRequestManagerMock->expects($this->once())
      ->method('commitSuccessfulUpload')
      ->with($translate_job)
      ->willReturn(FALSE);

    $this->apiWrapperMock->expects($this->at(2))
      ->method('createFirebaseRecord')
      ->with('tmgmt_smartling', 'notifications', 10, [
        "message" => 'File uploaded. Job id: 1, file name: JobID1_en_de.xml.',
        "type" => "status",
      ]);

    $this->apiWrapperMock->expects($this->at(4))
      ->method('createFirebaseRecord')
      ->with('tmgmt_smartling', 'notifications', 10, [
        "message" => "Finished: content is in the job. You may need to wait a few seconds before content is authorized (if you checked 'authorize' checkbox).",
        "type" => "status",
      ]);

    $this->apiWrapperMock->expects($this->at(5))
      ->method('createFirebaseRecord')
      ->with('tmgmt_smartling', 'notifications', 10, [
        "message" => "Can't update submitted date for translation request. See logs for more info.",
        "type" => "warning",
      ]);

    $translate_job->requestTranslation();
  }

  /**
   * Upload success flow without batch execution.
   */
  public function testRequestTranslationSuccessFlowDoNotExecute() {
    $translate_job = $this->createJobWithItems([
      'batch_uid' => 'uid',
      'batch_execute_on_job' => 2,
    ]);

    $this->translationRequestManagerMock->expects($this->once())
      ->method('upsertTranslationRequest')
      ->with($translate_job)
      ->willReturn(['translationRequestUid' => 'test']);

    $this->translationRequestManagerMock->expects($this->never())
      ->method('commitError');

    $this->apiWrapperMock->expects($this->once())
      ->method('getApi')
      ->with('batch')
      ->willReturn($this->batchApiMock);

    $this->apiWrapperMock->expects($this->once())
      ->method('createAuditLogRecord')
      ->with(
        $translate_job,
        NULL,
        \Drupal::currentUser(),
        CreateRecordParameters::ACTION_TYPE_UPLOAD
      );

    $this->batchApiMock->expects($this->once())
      ->method('uploadBatchFile')
      ->with(
        $this->callback(function($subject) {
          return strstr($subject, '/files/tmgmt_sources/JobID1_en_de.xml') !== FALSE;
        }),
        'JobID1_en_de.xml',
        'xml',
        'uid',
        $this->callback(function($subject) {
          $params = $subject->exportToArray();

          return $params['authorize'] == 0 &&
          preg_match('/^{"client":"drupal-tmgmt-connector","version":"(\d+\.x-\d+\.\d+|\d+\.x-\d+\.x-dev|unknown)"}$/', $params['smartling.client_lib_id']) &&
          $params['localeIdsToAuthorize'][0] == 'de' &&
          $params['smartling.translate_paths'] == 'html/body/div/div, html/body/div/span' &&
          $params['smartling.string_format_paths'] == 'html : html/body/div/div, @default : html/body/div/span' &&
          $params['smartling.variants_enabled'] == TRUE &&
          $params['smartling.source_key_paths'] == 'html/body/div/{div.sl-variant}, html/body/div/{span.sl-variant}' &&
          $params['smartling.character_limit_paths'] == 'html/body/div/limit' &&
          $params['smartling.placeholder_format_custom'] == '(@|%|!)[\w-]+';
        })
      );

    $this->apiWrapperMock->expects($this->never())
      ->method('executeBatch')
      ->with('uid');

    $this->translationRequestManagerMock->expects($this->once())
      ->method('commitSuccessfulUpload')
      ->with($translate_job)
      ->willReturn(TRUE);

    $this->apiWrapperMock->expects($this->once())
      ->method('createFirebaseRecord')
      ->with('tmgmt_smartling', 'notifications', 10, [
        "message" => 'File uploaded. Job id: 1, file name: JobID1_en_de.xml.',
        "type" => "status",
      ]);

    $translate_job->requestTranslation();
  }

  /**
   * Upload fail flow: no batch uid.
   */
  public function testRequestTranslationFailFlowNoBatchUid() {
    $this->translationRequestManagerMock->expects($this->never())
      ->method('upsertTranslationRequest');

    $this->translationRequestManagerMock->expects($this->never())
      ->method('commitError');

    $translate_job = $this->createJobWithItems([]);

    $this->apiWrapperMock->expects($this->once())
      ->method('createAuditLogRecord')
      ->with(
        $translate_job,
        NULL,
        \Drupal::currentUser(),
        CreateRecordParameters::ACTION_TYPE_UPLOAD
      );

    $this->apiWrapperMock->expects($this->never())
      ->method('getApi');

    $this->batchApiMock->expects($this->never())
      ->method('uploadBatchFile');

    $this->apiWrapperMock->expects($this->never())
      ->method('executeBatch');

    $this->translationRequestManagerMock->expects($this->never())
      ->method('commitSuccessfulUpload');

    $this->apiWrapperMock->expects($this->once())
      ->method('createFirebaseRecord')
      ->with('tmgmt_smartling', 'notifications', 10, [
        "message" => "File JobID1_en_de.xml (job id = 1) wasn't uploaded. Please see logs for more info.",
        "type" => "error",
      ]);

    $translate_job->requestTranslation();
  }

  /**
   * Upload fail flow: translation request upsert failed.
   */
  public function testRequestTranslationFailFlowTranslationRequestUpsertFailed() {
    $translate_job = $this->createJobWithItems([
      'batch_uid' => 'uid',
      'batch_execute_on_job' => 1,
    ]);

    $this->translationRequestManagerMock->expects($this->once())
      ->method('upsertTranslationRequest')
      ->with($translate_job)
      ->willReturn(FALSE);

    $this->translationRequestManagerMock->expects($this->never())
      ->method('commitError');

    $this->apiWrapperMock->expects($this->never())
      ->method('getApi');

    $this->batchApiMock->expects($this->never())
      ->method('uploadBatchFile');

    $this->apiWrapperMock->expects($this->never())
      ->method('executeBatch');

    $this->translationRequestManagerMock->expects($this->never())
      ->method('commitSuccessfulUpload');

    $this->apiWrapperMock->expects($this->once())
      ->method('createFirebaseRecord')
      ->with('tmgmt_smartling', 'notifications', 10, [
        "message" => "Can't upsert translation request. File JobID1_en_de.xml (job id = 1) wasn't uploaded. Please see logs for more info.",
        "type" => "error",
      ]);

    $translate_job->requestTranslation();
  }

  /**
   * Upload fail flow: error while uploading.
   */
  public function testRequestTranslationFailFlowErrorWhileUploading() {
    $exception = new \Exception("Test");
    $translation_request = ['translationRequestUid' => 'test'];
    $translate_job = $this->createJobWithItems([
      'batch_uid' => 'uid',
      'batch_execute_on_job' => 1,
    ]);

    $this->translationRequestManagerMock->expects($this->once())
      ->method('upsertTranslationRequest')
      ->with($translate_job)
      ->willReturn($translation_request);

    $this->apiWrapperMock->expects($this->once())
      ->method('getApi')
      ->with('batch')
      ->willReturn($this->batchApiMock);

    $this->apiWrapperMock->expects($this->once())
      ->method('createAuditLogRecord')
      ->with(
        $translate_job,
        NULL,
        \Drupal::currentUser(),
        CreateRecordParameters::ACTION_TYPE_UPLOAD
      );

    $this->apiWrapperMock->expects($this->once())
      ->method('executeBatch')
      ->with('uid')
      ->will($this->throwException($exception));

    $this->translationRequestManagerMock->expects($this->never())
      ->method('commitSuccessfulUpload');

    $this->apiWrapperMock->expects($this->at(2))
      ->method('createFirebaseRecord')
      ->with('tmgmt_smartling', 'notifications', 10, [
        "message" => 'File uploaded. Job id: 1, file name: JobID1_en_de.xml.',
        "type" => "status",
      ]);

    $this->apiWrapperMock->expects($this->at(4))
      ->method('createFirebaseRecord')
      ->with('tmgmt_smartling', 'notifications', 10, [
        "message" => 'Error while uploading public://tmgmt_sources/JobID1_en_de.xml. Please see logs for more info.',
        "type" => "error",
      ]);

    $this->translationRequestManagerMock->expects($this->once())
      ->method('commitError')
      ->with($translate_job, $translation_request, $exception);

    $translate_job->requestTranslation();
  }

}
