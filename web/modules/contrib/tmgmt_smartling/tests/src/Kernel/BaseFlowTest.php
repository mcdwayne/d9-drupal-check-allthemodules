<?php

namespace Drupal\Tests\tmgmt_smartling\Kernel;

use Smartling\AuditLog\Params\CreateRecordParameters;

/**
 * Tests base flows.
 *
 * @group tmgmt_smartling
 */
class BaseFlowTest extends SmartlingTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $api_factory_mock = $this->getMockBuilder('\Drupal\tmgmt_smartling\Smartling\SmartlingApiFactory')
      ->setMethods(NULL)
      ->getMock();

    $logger_mock = $this->getMockBuilder('\Drupal\Core\Logger\LoggerChannel')
      ->setMethods(NULL)
      ->disableOriginalConstructor()
      ->getMock();

    $this->apiWrapperMock = $this->getMockBuilder('\Drupal\tmgmt_smartling\Smartling\SmartlingApiWrapper')
      ->setMethods([
        'deleteFile',
        'createAuditLogRecord'
      ])
      ->setConstructorArgs([$api_factory_mock, $logger_mock])
      ->getMock();

    \Drupal::getContainer()->set('tmgmt_smartling.smartling_api_wrapper', $this->apiWrapperMock);
  }

  /**
   * Delete file in Smartling dashboard when corresponding TMGMT job is deleted.
   */
  function testJobFileDeletionInDashboardSuccess() {
    $translate_job = $this->createJobWithItems([]);

    $this->apiWrapperMock->expects($this->once())
      ->method('createAuditLogRecord')
      ->with(
        $translate_job,
        NULL,
        \Drupal::currentUser(),
        CreateRecordParameters::ACTION_TYPE_DELETE
      );

    $this->apiWrapperMock->expects($this->once())
      ->method('deleteFile')
      ->with('JobID1_en_de.xml');

    $translate_job->delete();
  }

  /**
   * Delete file in Smartling dashboard when corresponding TMGMT job is deleted.
   *
   * No translator assigned to the job.
   */
  function testJobFileDeletionInDashboardNoTranslatorAssigned() {
    $this->apiWrapperMock->expects($this->never())
      ->method('createAuditLogRecord');

    $this->apiWrapperMock->expects($this->never())
      ->method('deleteFile');

    $translate_job = $this->createJobWithItems([], NULL);
    $translate_job->delete();
  }

  /**
   * Delete file in Smartling dashboard when corresponding TMGMT job is deleted.
   *
   * Not smartling translator assigned to the job.
   */
  function testJobFileDeletionInDashboardNotSmartlingTranslatorAssigned() {
    $this->apiWrapperMock->expects($this->never())
      ->method('createAuditLogRecord');

    $this->apiWrapperMock->expects($this->never())
      ->method('deleteFile');

    $translate_job = $this->createJobWithItems([], $this->default_translator->id());
    $translate_job->delete();
  }

  /**
   * Job canceling leads to audit log record creation.
   */
  function testJobCancelingLeadsToAuditLogRecordCreation() {
    $translate_job = $this->createJobWithItems([]);

    $this->apiWrapperMock->expects($this->once())
      ->method('createAuditLogRecord')
      ->with(
        $translate_job,
        NULL,
        \Drupal::currentUser(),
        CreateRecordParameters::ACTION_TYPE_CANCEL
      );

    $translate_job->getTranslatorPlugin()->abortTranslation($translate_job);
  }

  /**
   * Translator settings update leads to audit log record creation.
   */
  function testTranslatorSettingsUpdateLeadsToAuditLogRecordCreation() {
    $translate_job = $this->createJobWithItems([]);
    $translator = $translate_job->getTranslator();

    $this->apiWrapperMock->expects($this->once())
      ->method('createAuditLogRecord')
      ->with(
        NULL,
        $translator,
        \Drupal::currentUser(),
        CreateRecordParameters::ACTION_TYPE_UPDATE_SETTINGS
      );

    $translator->save();
  }
}
