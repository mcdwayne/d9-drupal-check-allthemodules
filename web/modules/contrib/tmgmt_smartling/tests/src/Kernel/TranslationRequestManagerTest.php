<?php

namespace Drupal\Tests\tmgmt_smartling\Kernel;

use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt_smartling\Smartling\Submission\TranslationRequestManager;
use Smartling\TranslationRequests\Params\CreateTranslationRequestParams;
use Smartling\TranslationRequests\Params\CreateTranslationSubmissionParams;
use Smartling\TranslationRequests\Params\SearchTranslationRequestParams;
use Smartling\TranslationRequests\Params\TranslationSubmissionStates;
use Smartling\TranslationRequests\Params\UpdateTranslationRequestParams;
use Smartling\TranslationRequests\Params\UpdateTranslationSubmissionParams;

/**
 * Tests TranslationRequestManagerTest class.
 *
 * @group tmgmt_smartling
 */
class TranslationRequestManagerTest extends SmartlingTestBase {

  private $job;
  private $stateMock;
  private $loggerMock;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $api_factory_mock = $this->getMockBuilder('\Drupal\tmgmt_smartling\Smartling\SmartlingApiFactory')
      ->setMethods(NULL)
      ->getMock();

    $this->loggerMock = $this->getMockBuilder('\Drupal\Core\Logger\LoggerChannel')
      ->setMethods(NULL)
      ->disableOriginalConstructor()
      ->getMock();

    $this->apiWrapperMock = $this->getMockBuilder('\Drupal\tmgmt_smartling\Smartling\SmartlingApiWrapper')
      ->setConstructorArgs([$api_factory_mock, $this->loggerMock])
      ->getMock();

    $this->stateMock = $this->getMockBuilder('\Drupal\Core\State\State')
      ->setMethods(['get'])
      ->disableOriginalConstructor()
      ->getMock();

    $this->stateMock->expects($this->any())
      ->method('get')
      ->with('tmgmt_smartling.bucket_name')
      ->willReturn('tmgmt_smartling_bucket');

    $this->job = $this->createJobWithItems([
      'batch_uid' => 'uid',
      'batch_execute_on_job' => 1,
    ]);
  }

  /**
   * Create translation request: empty response from get request.
   */
  public function testUpsertTranslationRequestCreateEmptyGetResponse() {
    $translation_request_manager_mock = $this
      ->getMockBuilder(TranslationRequestManager::class)
      ->setConstructorArgs([$this->apiWrapperMock, $this->stateMock, $this->loggerMock])
      ->setMethods([
        'initApiWrapper',
        'getTranslationRequest',
        'isTranslationSubmissionMissed',
        'updateExistingTranslationRequest',
        'createNewTranslationRequest',
      ])
      ->getMock();

    $translation_request_manager_mock->expects($this->once())
      ->method('initApiWrapper')
      ->with($this->job);

    $translation_request_manager_mock->expects($this->once())
      ->method('getTranslationRequest')
      ->with($this->job)
      ->willReturn([]);

    $translation_request_manager_mock->expects($this->never())
      ->method('isTranslationSubmissionMissed');

    $translation_request_manager_mock->expects($this->once())
      ->method('createNewTranslationRequest')
      ->with($this->job);

    $translation_request_manager_mock->expects($this->never())
      ->method('updateExistingTranslationRequest');

    $translation_request_manager_mock->upsertTranslationRequest($this->job);
  }

  /**
   * Create translation request: missing submission in get response.
   */
  public function testUpsertTranslationRequestCreateMissingSubmissionInGetResponse() {
    $translation_request = [
      'translationRequestUid' => 'test',
      'translationSubmissions' => []
    ];

    $translation_request_manager_mock = $this
      ->getMockBuilder(TranslationRequestManager::class)
      ->setConstructorArgs([$this->apiWrapperMock, $this->stateMock, $this->loggerMock])
      ->setMethods([
        'initApiWrapper',
        'getTranslationRequest',
        'isTranslationSubmissionMissed',
        'updateExistingTranslationRequest',
        'createNewTranslationRequest',
      ])
      ->getMock();

    $translation_request_manager_mock->expects($this->once())
      ->method('initApiWrapper')
      ->with($this->job);

    $translation_request_manager_mock->expects($this->once())
      ->method('getTranslationRequest')
      ->with($this->job)
      ->willReturn($translation_request);

    $translation_request_manager_mock->expects($this->once())
      ->method('isTranslationSubmissionMissed')
      ->with($translation_request)
      ->willReturn(TRUE);

    $translation_request_manager_mock->expects($this->once())
      ->method('createNewTranslationRequest')
      ->with($this->job);

    $translation_request_manager_mock->expects($this->never())
      ->method('updateExistingTranslationRequest');

    $translation_request_manager_mock->upsertTranslationRequest($this->job);
  }

  /**
   * Update translation request: not empty response from get request.
   */
  public function testUpsertTranslationRequestUpdateNotEmptyGetResponse() {
    $translation_request_manager_mock = $this
      ->getMockBuilder(TranslationRequestManager::class)
      ->setConstructorArgs([$this->apiWrapperMock, $this->stateMock, $this->loggerMock])
      ->setMethods([
        'initApiWrapper',
        'getTranslationRequest',
        'isTranslationSubmissionMissed',
        'updateExistingTranslationRequest',
        'createNewTranslationRequest',
      ])
      ->getMock();

    $translation_request_manager_mock->expects($this->once())
      ->method('initApiWrapper')
      ->with($this->job);

    $translation_request_manager_mock->expects($this->once())
      ->method('getTranslationRequest')
      ->with($this->job)
      ->willReturn([
        'translationRequestUid' => 'test',
        'translationSubmissions' => [
          [
            'translationSubmissionUid' => 'test'
          ]
        ]
      ]);

    $translation_request_manager_mock->expects($this->once())
      ->method('isTranslationSubmissionMissed')
      ->willReturn(FALSE);

    $translation_request_manager_mock->expects($this->never())
      ->method('createNewTranslationRequest');

    $translation_request_manager_mock->expects($this->once())
      ->method('updateExistingTranslationRequest')
      ->with($this->job);

    $translation_request_manager_mock->upsertTranslationRequest($this->job);
  }

  /**
   * Is translation submission missed: true.
   */
  public function testIsTranslationSubmissionMissedTrue() {
    $translation_request_manager_mock = $this
      ->getMockBuilder(TranslationRequestManager::class)
      ->setConstructorArgs([$this->apiWrapperMock, $this->stateMock, $this->loggerMock])
      ->setMethods(NULL)
      ->getMock();

    $this->assertEquals($translation_request_manager_mock->isTranslationSubmissionMissed([
      'translationRequestUid' => 'test',
    ]), TRUE);

    $this->assertEquals($translation_request_manager_mock->isTranslationSubmissionMissed([
      'translationRequestUid' => 'test',
      'translationSubmissions' => []
    ]), TRUE);

    $this->assertEquals($translation_request_manager_mock->isTranslationSubmissionMissed([
      'translationRequestUid' => 'test',
      'translationSubmissions' => [
        []
      ]
    ]), TRUE);
  }

  /**
   * Is translation submission missed: false.
   */
  public function testIsTranslationSubmissionMissedFalse() {
    $translation_request_manager_mock = $this
      ->getMockBuilder(TranslationRequestManager::class)
      ->setConstructorArgs([$this->apiWrapperMock, $this->stateMock, $this->loggerMock])
      ->setMethods(NULL)
      ->getMock();

    $this->assertEquals($translation_request_manager_mock->isTranslationSubmissionMissed([
      'translationRequestUid' => 'test',
      'translationSubmissions' => [
        [
          'translationSubmissionUid' => 'test'
        ]
      ]
    ]), FALSE);
  }

  /**
   * Get request test.
   */
  public function testGetTranslationRequest() {
    $asset_key = ['tmgmt_job_id' => $this->job->id()];
    $search_params = new SearchTranslationRequestParams();
    $search_params->setOriginalAssetKey($asset_key);
    $search_params->setTargetAssetKey($asset_key);
    $search_params->setFileUri($this->job->getTranslatorPlugin()->getFileName($this->job));
    $search_params->setTargetLocaleId($this->job->getRemoteTargetLanguage());

    $translation_request_manager_mock = $this
      ->getMockBuilder(TranslationRequestManager::class)
      ->setConstructorArgs([$this->apiWrapperMock, $this->stateMock, $this->loggerMock])
      ->setMethods([
        'initApiWrapper'
      ])
      ->getMock();

    $translation_request_manager_mock->expects($this->once())
      ->method('initApiWrapper')
      ->with($this->job);

    $this->apiWrapperMock->expects($this->once())
      ->method('searchTranslationRequest')
      ->with('tmgmt_smartling_bucket', $search_params);

    $translation_request_manager_mock->getTranslationRequest($this->job);
  }

  /**
   * Test create new request test.
   */
  public function testCreateNewTranslationRequest() {
    $asset_key = ['tmgmt_job_id' => $this->job->id()];

    $create_submission_params = new CreateTranslationSubmissionParams();
    $create_submission_params
      ->setTargetAssetKey($asset_key)
      ->setTargetLocaleId($this->job->getRemoteTargetLanguage())
      ->setCustomTranslationData([
        'batch_uid' => $this->job->getSetting('batch_uid'),
        'batch_execute_on_job' => $this->job->getSetting('batch_execute_on_job')
      ])
      ->setState(TranslationSubmissionStates::STATE_NEW)
      ->setSubmitterName('test_submitter');

    $create_request_params = new CreateTranslationRequestParams();
    $create_request_params
      ->setOriginalAssetKey($asset_key)
      ->setTitle($this->job->label())
      ->setFileUri($this->job->getTranslatorPlugin()->getFileName($this->job))
      ->setOriginalLocaleId($this->job->getSourceLangcode())
      ->addTranslationSubmission($create_submission_params);

    $translation_request_manager_mock = $this
      ->getMockBuilder(TranslationRequestManagerTested::class)
      ->setConstructorArgs([$this->apiWrapperMock, $this->stateMock, $this->loggerMock])
      ->setMethods([
        'getSubmitterName',
      ])
      ->getMock();

    $translation_request_manager_mock->expects($this->once())
      ->method('getSubmitterName')
      ->with($this->job)
      ->willReturn('test_submitter');

    $this->apiWrapperMock->expects($this->once())
      ->method('createTranslationRequest')
      ->with('tmgmt_smartling_bucket', $create_request_params);

    $translation_request_manager_mock->createNewTranslationRequest($this->job);
  }

  /**
   * Test update existing request test.
   */
  public function testUpdateExistingTranslationRequest() {
    $translation_request = [
      'translationRequestUid' => 'test_translation_request_uid',
      'translationSubmissions' => [
        [
          'translationSubmissionUid' => 'test_translation_submission_uid'
        ]
      ]
    ];

    $update_submission_params = new UpdateTranslationSubmissionParams();
    $update_submission_params
      ->setSubmitterName('test_submitter')
      ->setCustomTranslationData([
        'batch_uid' => $this->job->getSetting('batch_uid'),
        'batch_execute_on_job' => $this->job->getSetting('batch_execute_on_job')
      ])
      ->setState(TranslationSubmissionStates::STATE_NEW)
      ->setTranslationSubmissionUid($translation_request['translationSubmissions'][0]['translationSubmissionUid']);

    $update_request_params = new UpdateTranslationRequestParams();
    $update_request_params
      ->setTitle($this->job->label())
      ->addTranslationSubmission($update_submission_params);

    $translation_request_manager_mock = $this
      ->getMockBuilder(TranslationRequestManagerTested::class)
      ->setConstructorArgs([$this->apiWrapperMock, $this->stateMock, $this->loggerMock])
      ->setMethods([
        'getSubmitterName',
      ])
      ->getMock();

    $translation_request_manager_mock->expects($this->once())
      ->method('getSubmitterName')
      ->with($this->job)
      ->willReturn('test_submitter');

    $this->apiWrapperMock->expects($this->once())
      ->method('updateTranslationRequest')
      ->with('tmgmt_smartling_bucket', 'test_translation_request_uid', $update_request_params);

    $translation_request_manager_mock->updateExistingTranslationRequest($this->job, $translation_request);
  }

  /**
   * Test upload success flow.
   */
  public function testCommitSuccessfulUpload() {
    $translation_request = [
      'translationRequestUid' => 'test_translation_request_uid',
      'translationSubmissions' => [
        [
          'translationSubmissionUid' => 'test_translation_submission_uid'
        ]
      ]
    ];

    $translation_request_manager_mock = $this
      ->getMockBuilder(TranslationRequestManager::class)
      ->setConstructorArgs([$this->apiWrapperMock, $this->stateMock, $this->loggerMock])
      ->setMethods([
        'initApiWrapper',
      ])
      ->getMock();

    $translation_request_manager_mock->expects($this->once())
      ->method('initApiWrapper')
      ->with($this->job);

    $update_submission_params = new UpdateTranslationSubmissionParams();
    $update_submission_params
      ->setSubmittedDate(new \DateTime('now', new \DateTimeZone('UTC')))
      ->setTranslationSubmissionUid($translation_request['translationSubmissions'][0]['translationSubmissionUid']);

    $update_request_params = new UpdateTranslationRequestParams();
    $update_request_params
      ->addTranslationSubmission($update_submission_params);

    $this->apiWrapperMock->expects($this->once())
      ->method('updateTranslationRequest')
      ->with('tmgmt_smartling_bucket', 'test_translation_request_uid', $update_request_params);

    $translation_request_manager_mock->commitSuccessfulUpload($this->job, $translation_request);
  }

  /**
   * Test upload success flow: no submission in request.
   */
  public function testCommitSuccessfulUploadNoSubmissionInRequest() {
    $translation_request = [
      'translationRequestUid' => 'test_translation_request_uid',
      'translationSubmissions' => []
    ];

    $translation_request_manager_mock = $this
      ->getMockBuilder(TranslationRequestManager::class)
      ->setConstructorArgs([$this->apiWrapperMock, $this->stateMock, $this->loggerMock])
      ->setMethods([
        'initApiWrapper',
      ])
      ->getMock();

    $translation_request_manager_mock->expects($this->once())
      ->method('initApiWrapper')
      ->with($this->job);

    $this->apiWrapperMock->expects($this->never())
      ->method('updateTranslationRequest');

    $translation_request_manager_mock->commitSuccessfulUpload($this->job, $translation_request);
  }

  /**
   * Test error flow.
   */
  public function testCommitError() {
    $exception = new \Exception('Test');
    $translation_request = [
      'translationRequestUid' => 'test_translation_request_uid',
      'translationSubmissions' => [
        [
          'translationSubmissionUid' => 'test_translation_submission_uid'
        ]
      ]
    ];

    $translation_request_manager_mock = $this
      ->getMockBuilder(TranslationRequestManager::class)
      ->setConstructorArgs([$this->apiWrapperMock, $this->stateMock, $this->loggerMock])
      ->setMethods([
        'initApiWrapper',
      ])
      ->getMock();

    $translation_request_manager_mock->expects($this->once())
      ->method('initApiWrapper')
      ->with($this->job);

    $update_submission_params = new UpdateTranslationSubmissionParams();
    $update_submission_params
      ->setState(TranslationSubmissionStates::STATE_FAILED)
      ->setLastErrorMessage(mb_substr($exception->getMessage() . ': ' . $exception->getTraceAsString(), 0, 1024))
      ->setTranslationSubmissionUid($translation_request['translationSubmissions'][0]['translationSubmissionUid']);

    $update_request_params = new UpdateTranslationRequestParams();
    $update_request_params
      ->addTranslationSubmission($update_submission_params);

    $this->apiWrapperMock->expects($this->once())
      ->method('updateTranslationRequest')
      ->with('tmgmt_smartling_bucket', 'test_translation_request_uid', $update_request_params);

    $translation_request_manager_mock->commitError($this->job, $translation_request, $exception);
  }

  /**
   * Test error flow: no submission in request.
   */
  public function testCommitErrorNoSubmissionInRequest() {
    $exception = new \Exception('Test');
    $translation_request = [
      'translationRequestUid' => 'test_translation_request_uid',
      'translationSubmissions' => []
    ];

    $translation_request_manager_mock = $this
      ->getMockBuilder(TranslationRequestManager::class)
      ->setConstructorArgs([$this->apiWrapperMock, $this->stateMock, $this->loggerMock])
      ->setMethods([
        'initApiWrapper',
      ])
      ->getMock();

    $translation_request_manager_mock->expects($this->once())
      ->method('initApiWrapper')
      ->with($this->job);

    $this->apiWrapperMock->expects($this->never())
      ->method('updateTranslationRequest');

    $translation_request_manager_mock->commitError($this->job, $translation_request, $exception);
  }

  /**
   * Test download success flow: change state.
   */
  public function testCommitSuccessfulDownloadChangeState() {
    $translation_request = [
      'translationRequestUid' => 'test_translation_request_uid',
      'translationSubmissions' => [
        [
          'translationSubmissionUid' => 'test_translation_submission_uid',
          'state' => TranslationSubmissionStates::STATE_TRANSLATED,
        ]
      ]
    ];

    $translation_request_manager_mock = $this
      ->getMockBuilder(TranslationRequestManager::class)
      ->setConstructorArgs([$this->apiWrapperMock, $this->stateMock, $this->loggerMock])
      ->setMethods([
        'initApiWrapper',
      ])
      ->getMock();

    $translation_request_manager_mock->expects($this->once())
      ->method('initApiWrapper')
      ->with($this->job);

    $update_submission_params = new UpdateTranslationSubmissionParams();
    $update_submission_params
      ->setLastExportedDate(new \DateTime('now', new \DateTimeZone('UTC')))
      ->setTranslationSubmissionUid($translation_request['translationSubmissions'][0]['translationSubmissionUid'])
      ->setState(TranslationSubmissionStates::STATE_COMPLETED);

    $update_request_params = new UpdateTranslationRequestParams();
    $update_request_params
      ->addTranslationSubmission($update_submission_params);

    $this->apiWrapperMock->expects($this->once())
      ->method('updateTranslationRequest')
      ->with('tmgmt_smartling_bucket', 'test_translation_request_uid', $update_request_params);

    $translation_request_manager_mock->commitSuccessfulDownload($this->job, $translation_request);
  }

  /**
   * Test download success flow: do not change state.
   */
  public function testCommitSuccessfulDownloadDoNotChangeState() {
    $translation_request = [
      'translationRequestUid' => 'test_translation_request_uid',
      'translationSubmissions' => [
        [
          'translationSubmissionUid' => 'test_translation_submission_uid',
          'state' => TranslationSubmissionStates::STATE_IN_PROGRESS,
        ]
      ]
    ];

    $translation_request_manager_mock = $this
      ->getMockBuilder(TranslationRequestManager::class)
      ->setConstructorArgs([$this->apiWrapperMock, $this->stateMock, $this->loggerMock])
      ->setMethods([
        'initApiWrapper',
      ])
      ->getMock();

    $translation_request_manager_mock->expects($this->once())
      ->method('initApiWrapper')
      ->with($this->job);

    $update_submission_params = new UpdateTranslationSubmissionParams();
    $update_submission_params
      ->setLastExportedDate(new \DateTime('now', new \DateTimeZone('UTC')))
      ->setTranslationSubmissionUid($translation_request['translationSubmissions'][0]['translationSubmissionUid']);

    $update_request_params = new UpdateTranslationRequestParams();
    $update_request_params
      ->addTranslationSubmission($update_submission_params);

    $this->apiWrapperMock->expects($this->once())
      ->method('updateTranslationRequest')
      ->with('tmgmt_smartling_bucket', 'test_translation_request_uid', $update_request_params);

    $translation_request_manager_mock->commitSuccessfulDownload($this->job, $translation_request);
  }

  /**
   * Test download success flow: no submission in request.
   */
  public function testCommitSuccessfulDownloadNoSubmissionInRequest() {
    $translation_request = [
      'translationRequestUid' => 'test_translation_request_uid',
      'translationSubmissions' => []
    ];

    $translation_request_manager_mock = $this
      ->getMockBuilder(TranslationRequestManager::class)
      ->setConstructorArgs([$this->apiWrapperMock, $this->stateMock, $this->loggerMock])
      ->setMethods([
        'initApiWrapper',
      ])
      ->getMock();

    $translation_request_manager_mock->expects($this->once())
      ->method('initApiWrapper')
      ->with($this->job);

    $this->apiWrapperMock->expects($this->never())
      ->method('updateTranslationRequest');

    $translation_request_manager_mock->commitSuccessfulDownload($this->job, $translation_request);
  }

  /**
   * Is translation submission missed: true, state translated.
   */
  public function testIsTranslationRequestReadyForDownloadStateTranslatedTrue() {
    $translation_request_manager_mock = $this
      ->getMockBuilder(TranslationRequestManager::class)
      ->setConstructorArgs([$this->apiWrapperMock, $this->stateMock, $this->loggerMock])
      ->setMethods([
        'initApiWrapper',
        'getTranslationRequest',
      ])
      ->getMock();

    $translation_request_manager_mock->expects($this->once())
      ->method('initApiWrapper')
      ->with($this->job);

    $translation_request_manager_mock->expects($this->once())
      ->method('getTranslationRequest')
      ->with($this->job)
      ->willReturn([
        'translationRequestUid' => 'test',
        'translationSubmissions' => [
          [
            'state' => TranslationSubmissionStates::STATE_TRANSLATED
          ]
        ]
      ]);

    $this->assertEquals($translation_request_manager_mock->isTranslationRequestReadyForDownload($this->job), TRUE);
  }

  /**
   * Is translation submission missed: false, missing submission.
   */
  public function testIsTranslationRequestReadyForDownloadMissingSubmissionFalse() {
    $translation_request_manager_mock = $this
      ->getMockBuilder(TranslationRequestManager::class)
      ->setConstructorArgs([$this->apiWrapperMock, $this->stateMock, $this->loggerMock])
      ->setMethods([
        'initApiWrapper',
        'getTranslationRequest',
      ])
      ->getMock();

    $translation_request_manager_mock->expects($this->once())
      ->method('initApiWrapper')
      ->with($this->job);

    $translation_request_manager_mock->expects($this->once())
      ->method('getTranslationRequest')
      ->with($this->job)
      ->willReturn([]);

    $this->assertEquals($translation_request_manager_mock->isTranslationRequestReadyForDownload($this->job), FALSE);
  }

  /**
   * Is translation submission missed: false, state new.
   */
  public function testIsTranslationRequestReadyForDownloadStateNewFalse() {
    $translation_request_manager_mock = $this
      ->getMockBuilder(TranslationRequestManager::class)
      ->setConstructorArgs([$this->apiWrapperMock, $this->stateMock, $this->loggerMock])
      ->setMethods([
        'initApiWrapper',
        'getTranslationRequest',
      ])
      ->getMock();

    $translation_request_manager_mock->expects($this->once())
      ->method('initApiWrapper')
      ->with($this->job);

    $translation_request_manager_mock->expects($this->once())
      ->method('getTranslationRequest')
      ->with($this->job)
      ->willReturn([
        'translationRequestUid' => 'test',
        'translationSubmissions' => [
          [
            'state' => TranslationSubmissionStates::STATE_NEW
          ]
        ]
      ]);

    $this->assertEquals($translation_request_manager_mock->isTranslationRequestReadyForDownload($this->job), FALSE);
  }

  /**
   * Is translation submission missed: false, state in progress.
   */
  public function testIsTranslationRequestReadyForDownloadStateInProgressFalse() {
    $translation_request_manager_mock = $this
      ->getMockBuilder(TranslationRequestManager::class)
      ->setConstructorArgs([$this->apiWrapperMock, $this->stateMock, $this->loggerMock])
      ->setMethods([
        'initApiWrapper',
        'getTranslationRequest',
      ])
      ->getMock();

    $translation_request_manager_mock->expects($this->once())
      ->method('initApiWrapper')
      ->with($this->job);

    $translation_request_manager_mock->expects($this->once())
      ->method('getTranslationRequest')
      ->with($this->job)
      ->willReturn([
        'translationRequestUid' => 'test',
        'translationSubmissions' => [
          [
            'state' => TranslationSubmissionStates::STATE_IN_PROGRESS
          ]
        ]
      ]);

    $this->assertEquals($translation_request_manager_mock->isTranslationRequestReadyForDownload($this->job), FALSE);
  }

  /**
   * Is translation submission missed: false, state completed.
   */
  public function testIsTranslationRequestReadyForDownloadStateCompletedFalse() {
    $translation_request_manager_mock = $this
      ->getMockBuilder(TranslationRequestManager::class)
      ->setConstructorArgs([$this->apiWrapperMock, $this->stateMock, $this->loggerMock])
      ->setMethods([
        'initApiWrapper',
        'getTranslationRequest',
      ])
      ->getMock();

    $translation_request_manager_mock->expects($this->once())
      ->method('initApiWrapper')
      ->with($this->job);

    $translation_request_manager_mock->expects($this->once())
      ->method('getTranslationRequest')
      ->with($this->job)
      ->willReturn([
        'translationRequestUid' => 'test',
        'translationSubmissions' => [
          [
            'state' => TranslationSubmissionStates::STATE_COMPLETED
          ]
        ]
      ]);

    $this->assertEquals($translation_request_manager_mock->isTranslationRequestReadyForDownload($this->job), FALSE);
  }

  /**
   * Is translation submission missed: false, state deleted.
   */
  public function testIsTranslationRequestReadyForDownloadStateDeletedFalse() {
    $translation_request_manager_mock = $this
      ->getMockBuilder(TranslationRequestManager::class)
      ->setConstructorArgs([$this->apiWrapperMock, $this->stateMock, $this->loggerMock])
      ->setMethods([
        'initApiWrapper',
        'getTranslationRequest',
      ])
      ->getMock();

    $translation_request_manager_mock->expects($this->once())
      ->method('initApiWrapper')
      ->with($this->job);

    $translation_request_manager_mock->expects($this->once())
      ->method('getTranslationRequest')
      ->with($this->job)
      ->willReturn([
        'translationRequestUid' => 'test',
        'translationSubmissions' => [
          [
            'state' => TranslationSubmissionStates::STATE_DELETED
          ]
        ]
      ]);

    $this->assertEquals($translation_request_manager_mock->isTranslationRequestReadyForDownload($this->job), FALSE);
  }

  /**
   * Is translation submission missed: false, state failed.
   */
  public function testIsTranslationRequestReadyForDownloadStateFailedFalse() {
    $translation_request_manager_mock = $this
      ->getMockBuilder(TranslationRequestManager::class)
      ->setConstructorArgs([$this->apiWrapperMock, $this->stateMock, $this->loggerMock])
      ->setMethods([
        'initApiWrapper',
        'getTranslationRequest',
      ])
      ->getMock();

    $translation_request_manager_mock->expects($this->once())
      ->method('initApiWrapper')
      ->with($this->job);

    $translation_request_manager_mock->expects($this->once())
      ->method('getTranslationRequest')
      ->with($this->job)
      ->willReturn([
        'translationRequestUid' => 'test',
        'translationSubmissions' => [
          [
            'state' => TranslationSubmissionStates::STATE_FAILED
          ]
        ]
      ]);

    $this->assertEquals($translation_request_manager_mock->isTranslationRequestReadyForDownload($this->job), FALSE);
  }
}

/**
 * Test subclass class of TranslationRequestManager.
 *
 * Increased access level for createNewTranslationRequest and
 * updateExistingTranslationRequest methods.
 */
class TranslationRequestManagerTested extends TranslationRequestManager {
  public function createNewTranslationRequest(JobInterface $job) {
    return parent::createNewTranslationRequest($job);
  }

  public function updateExistingTranslationRequest(JobInterface $job, array $translation_request) {
    return parent::updateExistingTranslationRequest($job, $translation_request);
  }
}
