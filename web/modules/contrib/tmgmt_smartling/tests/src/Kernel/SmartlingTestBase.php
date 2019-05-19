<?php

namespace Drupal\Tests\tmgmt_smartling\Kernel;

use Drupal\Tests\tmgmt\Kernel\TMGMTKernelTestBase;
use Drupal\tmgmt_smartling\Smartling\Submission\TranslationRequestManager;
use Smartling\Batch\BatchApi;
use Smartling\File\FileApi;

/**
 * Smartling kernel test base class.
 *
 * @group tmgmt_smartling
 */
class SmartlingTestBase extends TMGMTKernelTestBase {

  public static $modules = ['tmgmt_smartling', 'tmgmt_extension_suit', 'tmgmt_file', 'file'];

  protected $apiWrapperMock;
  protected $batchApiMock;
  protected $fileApiMock;
  protected $translationRequestManagerMock;
  protected $pluginMock;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Suppress deprecation errors.
    set_error_handler(function($errno) {
      if ($errno == E_USER_DEPRECATED) {
        return TRUE;
      }

      return FALSE;
    });

    $this->installConfig(['tmgmt_smartling', 'tmgmt_extension_suit', 'tmgmt_file', 'file']);
    $this->installSchema('file', ['file_usage']);
    $this->installEntitySchema('file');
    $this->installEntitySchema('tmgmt_remote');

    $this->pluginMock = $this->getMockBuilder('\Drupal\tmgmt_smartling\Plugin\tmgmt_file\Format\Xml')
      ->setMethods(['validateImport', 'import'])
      ->getMock();

    $format_manager_mock = $this->getMockBuilder('\Drupal\tmgmt_file\Format\FormatManager')
      ->disableOriginalConstructor()
      ->setMethods(['createInstance'])
      ->getMock();

    $format_manager_mock->expects($this->any())
      ->method('createInstance')
      ->willReturn($this->pluginMock);

    $api_factory_mock = $this->getMockBuilder('\Drupal\tmgmt_smartling\Smartling\SmartlingApiFactory')
      ->setMethods(NULL)
      ->getMock();

    $logger_mock = $this->getMockBuilder('\Drupal\Core\Logger\LoggerChannel')
      ->setMethods(NULL)
      ->disableOriginalConstructor()
      ->getMock();

    $this->batchApiMock = $this->getMockBuilder(BatchApi::class)
      ->disableOriginalConstructor()
      ->setMethods(['uploadBatchFile'])
      ->getMock();

    $this->fileApiMock = $this->getMockBuilder(FileApi::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->translationRequestManagerMock = $this->getMockBuilder(TranslationRequestManager::class)
      ->disableOriginalConstructor()
      ->setMethods([
        'getSubmitterName',
        'getBucketName',
        'upsertTranslationRequest',
        'getTranslationRequest',
        'commitSuccessfulDownload',
        'commitSuccessfulUpload',
        'commitError'
      ])
      ->getMock();

    $this->translationRequestManagerMock->expects($this->any())
      ->method('getSubmitterName')
      ->willReturn('test_submitter_name');

    $this->translationRequestManagerMock->expects($this->any())
      ->method('getBucketName')
      ->willReturn('test_bucket_name');

    $this->apiWrapperMock = $this->getMockBuilder('\Drupal\tmgmt_smartling\Smartling\SmartlingApiWrapper')
      ->setMethods([
        'createFirebaseRecord',
        'getApi',
        'executeBatch',
        'searchTranslationRequest',
        'createAuditLogRecord'
      ])
      ->setConstructorArgs([$api_factory_mock, $logger_mock])
      ->getMock();

    \Drupal::getContainer()->set('tmgmt_smartling.smartling_api_wrapper', $this->apiWrapperMock);
    \Drupal::getContainer()->set('tmgmt_smartling.translation_request_manager', $this->translationRequestManagerMock);
    \Drupal::getContainer()->set('plugin.manager.tmgmt_file.format', $format_manager_mock);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    restore_error_handler();
  }

  /**
   * Creates a job.
   *
   * @param array $settings
   * @param string $translator
   * @return \Drupal\tmgmt\JobInterface
   */
  protected function createJobWithItems(array $settings, $translator = 'smartling') {
    $job = parent::createJob();

    for ($i = 1; $i < 3; $i++) {
      $job->addItem('test_source', 'test', $i);
    }

    $job->settings = $settings;
    $job->translator = $translator;

    return $job;
  }
}
