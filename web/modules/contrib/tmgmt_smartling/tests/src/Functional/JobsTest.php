<?php

namespace Drupal\Tests\tmgmt_smartling\Functional;

use Drupal;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\SchemaObjectExistsException;
use Drupal\Core\Queue\DatabaseQueue;
use Drupal\node\Entity\Node;
use Drupal\tmgmt\Entity\Job;
use Drupal\tmgmt\JobInterface;
use Smartling\Jobs\JobStatus;

/**
 * Jobs tests.
 *
 * @group tmgmt_smartling
 */
class JobsTest extends SmartlingTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create queue table (it doesn't exist for simpletests somehow).
    $uploadQueue = new DatabaseQueue('tmgmt_extension_suit_upload', Database::getConnection());
    $database_schema = Drupal::database()->schema();

    try {
      if (!$database_schema->tableExists('queue')) {
        $schema_definition = $uploadQueue->schemaDefinition();
        $database_schema->createTable('queue', $schema_definition);
      }
    }
    catch (SchemaObjectExistsException $e) {
    }

    $this->drupalPostForm('/admin/tmgmt/translators/manage/smartling', [
      'settings[project_id]' => $this->smartlingPluginProviderSettings['settings[project_id]'],
      'settings[user_id]' => $this->smartlingPluginProviderSettings['settings[user_id]'],
      'settings[token_secret]' => $this->smartlingPluginProviderSettings['settings[token_secret]'],
    ], 'Save');
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    $api_wrapper = Drupal::service('tmgmt_smartling.smartling_api_wrapper');
    $api_wrapper->setSettings([
      'user_id' => $this->smartlingPluginProviderSettings['settings[user_id]'],
      'project_id' => $this->smartlingPluginProviderSettings['settings[project_id]'],
      'token_secret' => $this->smartlingPluginProviderSettings['settings[token_secret]'],
    ]);
    $jobs = $api_wrapper->listJobs(NULL, [
      JobStatus::AWAITING_AUTHORIZATION,
      JobStatus::IN_PROGRESS,
    ]);

    if (!empty($jobs['items'])) {
      foreach ($jobs['items'] as $job) {
        if (strpos($job['jobName'], 'Drupal TMGMT connector test') !== FALSE) {
          $api_wrapper->cancelJob($job['translationJobUid']);
        }
      }
    }

    parent::tearDown();
  }

  /**
   * Skip jobs which don't have batch uid in settings.
   */
  public function testUploadJobWithoutBatchUid() {
    $job = $this->createJob($this->sourceLanguage, 'de', 1, [
      'job_type' => Job::TYPE_NORMAL,
    ]);
    $job->translator = 'smartling';
    $job->addItem('content', 'node', 1);
    $job->setState(JobInterface::STATE_ACTIVE);
    $job->requestTranslation();

    $this->drupalGet('/admin/reports/dblog');
    $this->assertUniqueText(t('File @name (job id = @job_id) wasn\'t uploaded', [
      '@name' => $job->getTranslatorPlugin()->getFileName($job),
      '@job_id' => $job->id(),
    ]));
    $this->assertNoText(t('Smartling updated a job'));
    $this->assertNoText(t('Smartling created a batch'));
    $this->assertNoText(t('File uploaded. Job id: @job_id.', [
      '@job_id' => $job->id(),
    ]));
    $this->assertNoText(t('Smartling executed a batch'));
  }

  /**
   * Test alter job name hook.
   */
  public function testBucketJobNameAlter() {
    $name = 'Test bucket job name';
    $bucket_job_manager = Drupal::service('tmgmt_smartling.bucket_job_manager');

    $reflection = new \ReflectionClass(get_class($bucket_job_manager));
    $method = $reflection->getMethod('getName');
    $method->setAccessible(true);

    $job_bucket_name = $method->invokeArgs($bucket_job_manager, []);
    $this->assertNotEqual($job_bucket_name, $name);

    \Drupal::service('module_installer')->install(['tmgmt_smartling_test_alter_filename']);

    $job_bucket_name = $method->invokeArgs($bucket_job_manager, []);
    $this->assertEqual($job_bucket_name, $name);
  }

  /**
   * Test bucket job.
   */
  public function testBucketJob() {
    if (!empty($this->smartlingPluginProviderSettings)) {
      // 1. Send content in a job.
      $this->drupalPostForm('/admin/tmgmt/sources', [
        'items[1]' => 1,
        'target_language' => '_all',
      ], t('Request translation'));

      $this->drupalPostForm(NULL, [
        'target_language' => 'de',
        'settings[create_new_job_tab][name]' => 'Drupal TMGMT connector test ' . mt_rand(),
        'settings[create_new_job_tab][due_date][date]' => '2020-12-12',
        'settings[create_new_job_tab][due_date][time]' => '12:12',
        'settings[create_new_job_tab][authorize]' => TRUE,
        'settings[smartling_users_time_zone]' => 'Europe/Kiev',
      ], t('Submit to provider and continue'));

      // 2. Check that upload queue is empty.
      $this->assertTrue($this->getCountOfItemsInQueue('tmgmt_extension_suit_upload') == 0, 'Upload queue is empty.');

      // 3. Update a node - trigger entity changes handling.
      $node = Node::load(1);
      $node->setTitle('Updated node title');
      $node->save();

      // 4. Check upload entity queue.
      $this->assertTrue($this->getCountOfItemsInQueue('tmgmt_extension_suit_upload') == 2, 'Upload queue contains 2 items.');

      $data1 = $this->fetchQueueItemsData('tmgmt_extension_suit_upload');
      $batch_uid_old_1 = $data1[0]['batch_uid'];
      $batch_uid_old_2 = $data1[1]['batch_uid'];

      $this->assertEqual($batch_uid_old_1, $batch_uid_old_2, 'First two files will be uploaded into the same batch.');
      $this->assertEqual($data1, [
        [
          'id' => 1,
          'batch_uid' => $batch_uid_old_1,
          'batch_execute_on_job' => 2,
        ],
        [
          'id' => 2,
          'batch_uid' => $batch_uid_old_1,
          'batch_execute_on_job' => 2,
        ]
      ], 'Queue items have valid data inside.');

      // 5. Update a node - trigger entity changes handling once again.
      $node = Node::load(1);
      $node->setTitle('Updated node title once again');
      $node->save();

      // 6. Check upload entity queue once again.
      $this->assertTrue($this->getCountOfItemsInQueue('tmgmt_extension_suit_upload') == 4, 'Upload queue contains 4 items.');

      $data2 = $this->fetchQueueItemsData('tmgmt_extension_suit_upload');
      $batch_uid_new_1 = $data2[2]['batch_uid'];
      $batch_uid_new_2 = $data2[3]['batch_uid'];

      $this->assertEqual($batch_uid_new_1, $batch_uid_new_2, 'Second two files will be uploaded into the same batch.');
      $this->assertNotEqual($batch_uid_old_1, $batch_uid_new_1, 'First and second batches are different.');
      $this->assertEqual($data2, [
        [
          'id' => 1,
          'batch_uid' => $batch_uid_old_1,
          'batch_execute_on_job' => 2,
        ],
        [
          'id' => 2,
          'batch_uid' => $batch_uid_old_1,
          'batch_execute_on_job' => 2,
        ],
        [
          'id' => 1,
          'batch_uid' => $batch_uid_new_1,
          'batch_execute_on_job' => 2,
        ],
        [
          'id' => 2,
          'batch_uid' => $batch_uid_new_1,
          'batch_execute_on_job' => 2,
        ]
      ], 'Queue items have valid data inside.');
    }
    else {
      $this->fail("Smartling settings file for simpletests not found.");
    }
  }

  /**
   * Create job form: empty name validation.
   */
  public function testCreateJobEmptyNameValidation() {
    if (!empty($this->smartlingPluginProviderSettings)) {
      $this->drupalPostForm('/admin/tmgmt/sources', [
        'items[1]' => 1,
      ], t('Request translation'));

      $this->drupalPostForm(NULL, [
        'target_language' => 'de',
        'settings[create_new_job_tab][name]' => '',
        'settings[create_new_job_tab][due_date][date]' => '2020-12-12',
        'settings[create_new_job_tab][due_date][time]' => '12:12',
        'settings[create_new_job_tab][authorize]' => TRUE,
        'settings[smartling_users_time_zone]' => 'Europe/Kiev',
      ], t('Submit to provider'));

      $this->assertText(t('Job Name field is required.'));
    }
    else {
      $this->fail("Smartling settings file for simpletests not found.");
    }
  }

  /**
   * Create job form: existing name validation.
   */
  public function testCreateJobExistingNameValidation() {
    if (!empty($this->smartlingPluginProviderSettings)) {
      $existing_job_name = 'Drupal TMGMT connector test: EXISTING JOB';

      $this->drupalPostForm('/admin/tmgmt/sources', [
        'items[1]' => 1,
      ], t('Request translation'));

      $this->drupalPostForm(NULL, [
        'target_language' => 'de',
        'settings[create_new_job_tab][name]' => $existing_job_name,
        'settings[create_new_job_tab][due_date][date]' => '2020-12-12',
        'settings[create_new_job_tab][due_date][time]' => '12:12',
        'settings[create_new_job_tab][authorize]' => TRUE,
        'settings[smartling_users_time_zone]' => 'Europe/Kiev',
      ], t('Submit to provider'));

      $this->drupalPostForm('/admin/tmgmt/sources', [
        'items[1]' => 1,
      ], t('Request translation'));

      $this->drupalPostForm(NULL, [
        'target_language' => 'de',
        'settings[create_new_job_tab][name]' => $existing_job_name,
        'settings[create_new_job_tab][due_date][date]' => '2020-12-12',
        'settings[create_new_job_tab][due_date][time]' => '12:12',
        'settings[create_new_job_tab][authorize]' => TRUE,
        'settings[smartling_users_time_zone]' => 'Europe/Kiev',
      ], t('Submit to provider'));

      $this->assertText(t('Job with name "@name" already exists. Please choose another job name.', [
        '@name' => $existing_job_name,
      ]));
    }
    else {
      $this->fail("Smartling settings file for simpletests not found.");
    }
  }

  /**
   * Create job form: due date validation.
   */
  public function testCreateJobDueDateValidation() {
    if (!empty($this->smartlingPluginProviderSettings)) {
      $this->drupalPostForm('/admin/tmgmt/sources', [
        'items[1]' => 1,
      ], t('Request translation'));

      $this->drupalPostForm(NULL, [
        'target_language' => 'de',
        'settings[create_new_job_tab][name]' => 'Test',
        'settings[create_new_job_tab][due_date][date]' => '2012-12-12',
        'settings[create_new_job_tab][due_date][time]' => '12:12',
        'settings[create_new_job_tab][authorize]' => TRUE,
        'settings[smartling_users_time_zone]' => 'Europe/Kiev',
      ], t('Submit to provider'));

      $this->assertText(t('Due date can not be in the past.'));
      $this->assertNoText(t('Please enter a date in the format'));
    }
    else {
      $this->fail("Smartling settings file for simpletests not found.");
    }
  }

  /**
   * Create job form: due date validation (invalid date).
   */
  public function testCreateJobDueDateValidationInvalidDate() {
    if (!empty($this->smartlingPluginProviderSettings)) {
      $this->drupalPostForm('/admin/tmgmt/sources', [
        'items[1]' => 1,
      ], t('Request translation'));

      $this->drupalPostForm(NULL, [
        'target_language' => 'de',
        'settings[create_new_job_tab][name]' => 'Test',
        'settings[create_new_job_tab][due_date][date]' => '2012-12-12',
        'settings[create_new_job_tab][due_date][time]' => '',
        'settings[create_new_job_tab][authorize]' => TRUE,
        'settings[smartling_users_time_zone]' => 'Europe/Kiev',
      ], t('Submit to provider'));

      $this->assertText(t('Please enter a date in the format'));
      $this->assertNoText(t('Due date can not be in the past.'));

      $this->drupalPostForm('/admin/tmgmt/sources', [
        'items[1]' => 1,
      ], t('Request translation'));

      $this->drupalPostForm(NULL, [
        'target_language' => 'de',
        'settings[create_new_job_tab][name]' => 'Test',
        'settings[create_new_job_tab][due_date][date]' => '',
        'settings[create_new_job_tab][due_date][time]' => '12:12',
        'settings[create_new_job_tab][authorize]' => TRUE,
        'settings[smartling_users_time_zone]' => 'Europe/Kiev',
      ], t('Submit to provider'));

      $this->assertText(t('Please enter a date in the format'));
      $this->assertNoText(t('Due date can not be in the past.'));
    }
    else {
      $this->fail("Smartling settings file for simpletests not found.");
    }
  }

  /**
   * Add to job form: due date validation.
   */
  public function testAddToJobDueDateValidation() {
    if (!empty($this->smartlingPluginProviderSettings)) {
      $this->drupalPostForm('/admin/tmgmt/sources', [
        'items[1]' => 1,
      ], t('Request translation'));

      $this->drupalPostForm(NULL, [
        'target_language' => 'de',
        'settings[switcher]' => TMGMT_SMARTLING_ADD_TO_JOB,
        'settings[add_to_job_tab][container][job_info][due_date][date]' => '2012-12-12',
        'settings[add_to_job_tab][container][job_info][due_date][time]' => '12:12',
        'settings[add_to_job_tab][container][job_info][authorize]' => TRUE,
        'settings[smartling_users_time_zone]' => 'Europe/Kiev',
      ], t('Submit to provider'));

      $this->assertText(t('Due date can not be in the past.'));
      $this->assertNoText(t('Please enter a date in the format'));
    }
    else {
      $this->fail("Smartling settings file for simpletests not found.");
    }
  }

  /**
   * Add to job form: due date validation (invalid date).
   */
  public function testAddToJobDueDateValidationInvalidDate() {
    if (!empty($this->smartlingPluginProviderSettings)) {
      $this->drupalPostForm('/admin/tmgmt/sources', [
        'items[1]' => 1,
      ], t('Request translation'));

      $this->drupalPostForm(NULL, [
        'target_language' => 'de',
        'settings[switcher]' => TMGMT_SMARTLING_ADD_TO_JOB,
        'settings[add_to_job_tab][container][job_info][due_date][date]' => '2012-12-12',
        'settings[add_to_job_tab][container][job_info][due_date][time]' => '',
        'settings[add_to_job_tab][container][job_info][authorize]' => TRUE,
        'settings[smartling_users_time_zone]' => 'Europe/Kiev',
      ], t('Submit to provider'));

      $this->assertText(t('Please enter a date in the format'));
      $this->assertNoText(t('Due date can not be in the past.'));

      $this->drupalPostForm('/admin/tmgmt/sources', [
        'items[1]' => 1,
      ], t('Request translation'));

      $this->drupalPostForm(NULL, [
        'target_language' => 'de',
        'settings[switcher]' => TMGMT_SMARTLING_ADD_TO_JOB,
        'settings[add_to_job_tab][container][job_info][due_date][date]' => '',
        'settings[add_to_job_tab][container][job_info][due_date][time]' => '12:12',
        'settings[add_to_job_tab][container][job_info][authorize]' => TRUE,
        'settings[smartling_users_time_zone]' => 'Europe/Kiev',
      ], t('Submit to provider'));

      $this->assertText(t('Please enter a date in the format'));
      $this->assertNoText(t('Due date can not be in the past.'));
    }
    else {
      $this->fail("Smartling settings file for simpletests not found.");
    }
  }

  /**
   * Create job form, sync mode: single job.
   */
  public function testCreateJobSingleSync() {
    if (!empty($this->smartlingPluginProviderSettings)) {
      $this->drupalPostForm('/admin/tmgmt/sources', [
        'items[1]' => 1,
      ], t('Request translation'));

      $this->drupalPostForm(NULL, [
        'target_language' => 'de',
        'settings[create_new_job_tab][name]' => 'Drupal TMGMT connector test ' . mt_rand(),
        'settings[create_new_job_tab][due_date][date]' => '2020-12-12',
        'settings[create_new_job_tab][due_date][time]' => '12:12',
        'settings[create_new_job_tab][authorize]' => TRUE,
        'settings[smartling_users_time_zone]' => 'Europe/Kiev',
      ], t('Submit to provider'));

      $job = Job::load(1);

      $this->drupalGet('/admin/reports/dblog');
      $this->assertUniqueText(t('Smartling created a job'));
      $this->assertNoText(t('Smartling updated a job'));
      $this->assertUniqueText(t('Smartling created a batch'));
      $this->assertUniqueText(t('File uploaded. Job id: @job_id, file name: @filename.', [
        '@job_id' => $job->id(),
        '@filename' => $job->getTranslatorPlugin()->getFileName($job),
      ]));
      $this->assertNoText(t('Fallback: File uploaded. Job id: @job_id, file name: @filename.', [
        '@job_id' => $job->id(),
        '@filename' => $job->getTranslatorPlugin()->getFileName($job),
      ]));
      $this->assertUniqueText(t('Smartling executed a batch'));

      $this->assertTrue($this->getCountOfItemsInQueue('smartling_context_upload') == 1);
    }
    else {
      $this->fail("Smartling settings file for simpletests not found.");
    }
  }

  /**
   * Create job form, sync mode: two jobs (queue mode).
   */
  public function testCreateJobQueueSync() {
    if (!empty($this->smartlingPluginProviderSettings)) {
      $this->drupalPostForm('/admin/tmgmt/sources', [
        'items[1]' => 1,
        'target_language' => '_all',
      ], t('Request translation'));

      $this->drupalPostForm(NULL, [
        'target_language' => 'de',
        'settings[create_new_job_tab][name]' => 'Drupal TMGMT connector test ' . mt_rand(),
        'settings[create_new_job_tab][due_date][date]' => '2020-12-12',
        'settings[create_new_job_tab][due_date][time]' => '12:12',
        'settings[create_new_job_tab][authorize]' => TRUE,
        'settings[smartling_users_time_zone]' => 'Europe/Kiev',
      ], t('Submit to provider and continue'));

      $job1 = Job::load(1);
      $job2 = Job::load(2);

      $this->drupalGet('/admin/reports/dblog');
      $this->assertUniqueText(t('Smartling created a job'));
      $this->assertNoText(t('Smartling updated a job'));
      $this->assertUniqueText(t('Smartling created a batch'));
      $this->assertUniqueText(t('File uploaded. Job id: @job_id, file name: @filename.', [
        '@job_id' => $job1->id(),
        '@filename' => $job1->getTranslatorPlugin()->getFileName($job1),
      ]));
      $this->assertNoText(t('Fallback: File uploaded. Job id: @job_id, file name: @filename.', [
        '@job_id' => $job1->id(),
        '@filename' => $job1->getTranslatorPlugin()->getFileName($job1),
      ]));
      $this->assertUniqueText(t('File uploaded. Job id: @job_id, file name: @filename.', [
        '@job_id' => $job2->id(),
        '@filename' => $job2->getTranslatorPlugin()->getFileName($job2),
      ]));
      $this->assertNoText(t('Fallback: File uploaded. Job id: @job_id, file name: @filename.', [
        '@job_id' => $job2->id(),
        '@filename' => $job2->getTranslatorPlugin()->getFileName($job2),
      ]));
      $this->assertUniqueText(t('Smartling executed a batch'));

      $this->assertTrue($this->getCountOfItemsInQueue('smartling_context_upload') == 2);
    }
    else {
      $this->fail("Smartling settings file for simpletests not found.");
    }
  }

  /**
   * Create job form, async mode: single job.
   */
  public function testCreateJobSingleAsync() {
    if (!empty($this->smartlingPluginProviderSettings)) {
      $this->drupalPostForm('/admin/tmgmt/translators/manage/smartling', [
        'settings[async_mode]' => TRUE,
      ], t('Save'));

      $this->drupalPostForm('/admin/tmgmt/sources', [
        'items[1]' => 1,
      ], t('Request translation'));

      $this->drupalPostForm(NULL, [
        'target_language' => 'de',
        'settings[create_new_job_tab][name]' => 'Drupal TMGMT connector test ' . mt_rand(),
        'settings[create_new_job_tab][due_date][date]' => '2020-12-12',
        'settings[create_new_job_tab][due_date][time]' => '12:12',
        'settings[create_new_job_tab][authorize]' => TRUE,
        'settings[smartling_users_time_zone]' => 'Europe/Kiev',
      ], t('Submit to provider'));

      $this->assertText(t('Job has been put into upload queue.'));

      $job = Job::load(1);

      $this->drupalGet('/admin/reports/dblog');
      $this->assertUniqueText(t('Smartling created a job'));
      $this->assertNoText(t('Smartling updated a job'));
      $this->assertUniqueText(t('Smartling created a batch'));
      $this->assertNoText(t('File uploaded. Job id: @job_id, file name: @filename.', [
        '@job_id' => $job->id(),
        '@filename' => $job->getTranslatorPlugin()->getFileName($job),
      ]));
      $this->assertNoText(t('Fallback: File uploaded. Job id: @job_id, file name: @filename.', [
        '@job_id' => $job->id(),
        '@filename' => $job->getTranslatorPlugin()->getFileName($job),
      ]));
      $this->assertNoText(t('Smartling executed a batch'));

      $this->assertTrue($this->getCountOfItemsInQueue('tmgmt_extension_suit_upload') == 1);
      $this->assertTrue($this->getCountOfItemsInQueue('smartling_context_upload') == 0);

      $this->processQueue('tmgmt_extension_suit_upload');

      $this->drupalGet('/admin/reports/dblog');
      $this->assertUniqueText(t('Smartling created a job'));
      $this->assertNoText(t('Smartling updated a job'));
      $this->assertUniqueText(t('Smartling created a batch'));
      $this->assertUniqueText(t('File uploaded. Job id: @job_id, file name: @filename.', [
        '@job_id' => $job->id(),
        '@filename' => $job->getTranslatorPlugin()->getFileName($job),
      ]));
      $this->assertNoText(t('Fallback: File uploaded. Job id: @job_id, file name: @filename.', [
        '@job_id' => $job->id(),
        '@filename' => $job->getTranslatorPlugin()->getFileName($job),
      ]));
      $this->assertUniqueText(t('Smartling executed a batch'));

      $this->assertTrue($this->getCountOfItemsInQueue('tmgmt_extension_suit_upload') == 0);
      $this->assertTrue($this->getCountOfItemsInQueue('smartling_context_upload') == 1);
    }
    else {
      $this->fail("Smartling settings file for simpletests not found.");
    }
  }

  /**
   * Create job form, async mode: two jobs (queue mode).
   */
  public function testCreateJobQueueAsync() {
    if (!empty($this->smartlingPluginProviderSettings)) {
      $this->drupalPostForm('/admin/tmgmt/translators/manage/smartling', [
        'settings[async_mode]' => TRUE,
      ], t('Save'));

      $this->drupalPostForm('/admin/tmgmt/sources', [
        'items[1]' => 1,
        'target_language' => '_all',
      ], t('Request translation'));

      $this->drupalPostForm(NULL, [
        'target_language' => 'de',
        'settings[create_new_job_tab][name]' => 'Drupal TMGMT connector test ' . mt_rand(),
        'settings[create_new_job_tab][due_date][date]' => '2020-12-12',
        'settings[create_new_job_tab][due_date][time]' => '12:12',
        'settings[create_new_job_tab][authorize]' => TRUE,
        'settings[smartling_users_time_zone]' => 'Europe/Kiev',
      ], t('Submit to provider and continue'));

      $this->assertText(t('Job has been put into upload queue.'));

      $job1 = Job::load(1);
      $job2 = Job::load(2);

      $this->drupalGet('/admin/reports/dblog');
      $this->assertUniqueText(t('Smartling created a job'));
      $this->assertNoText(t('Smartling updated a job'));
      $this->assertUniqueText(t('Smartling created a batch'));
      $this->assertNoText(t('File uploaded. Job id: @job_id, file name: @filename.', [
        '@job_id' => $job1->id(),
        '@filename' => $job1->getTranslatorPlugin()->getFileName($job1),
      ]));
      $this->assertNoText(t('Fallback: File uploaded. Job id: @job_id, file name: @filename.', [
        '@job_id' => $job1->id(),
        '@filename' => $job1->getTranslatorPlugin()->getFileName($job1),
      ]));
      $this->assertNoText(t('File uploaded. Job id: @job_id, file name: @filename.', [
        '@job_id' => $job2->id(),
        '@filename' => $job2->getTranslatorPlugin()->getFileName($job2),
      ]));
      $this->assertNoText(t('Fallback: File uploaded. Job id: @job_id, file name: @filename.', [
        '@job_id' => $job2->id(),
        '@filename' => $job2->getTranslatorPlugin()->getFileName($job2),
      ]));
      $this->assertNoText(t('Smartling executed a batch'));

      $this->assertTrue($this->getCountOfItemsInQueue('tmgmt_extension_suit_upload') == 2);
      $this->assertTrue($this->getCountOfItemsInQueue('smartling_context_upload') == 0);

      $this->processQueue('tmgmt_extension_suit_upload');

      $this->drupalGet('/admin/reports/dblog');
      $this->assertUniqueText(t('Smartling created a job'));
      $this->assertNoText(t('Smartling updated a job'));
      $this->assertUniqueText(t('Smartling created a batch'));
      $this->assertUniqueText(t('File uploaded. Job id: @job_id, file name: @filename.', [
        '@job_id' => $job1->id(),
        '@filename' => $job1->getTranslatorPlugin()->getFileName($job1),
      ]));
      $this->assertNoText(t('Fallback: File uploaded. Job id: @job_id, file name: @filename.', [
        '@job_id' => $job1->id(),
        '@filename' => $job1->getTranslatorPlugin()->getFileName($job1),
      ]));
      $this->assertUniqueText(t('File uploaded. Job id: @job_id, file name: @filename.', [
        '@job_id' => $job2->id(),
        '@filename' => $job2->getTranslatorPlugin()->getFileName($job2),
      ]));
      $this->assertNoText(t('Fallback: File uploaded. Job id: @job_id, file name: @filename.', [
        '@job_id' => $job2->id(),
        '@filename' => $job2->getTranslatorPlugin()->getFileName($job2),
      ]));
      $this->assertUniqueText(t('Smartling executed a batch'));

      $this->assertTrue($this->getCountOfItemsInQueue('tmgmt_extension_suit_upload') == 0);
      $this->assertTrue($this->getCountOfItemsInQueue('smartling_context_upload') == 2);
    }
    else {
      $this->fail("Smartling settings file for simpletests not found.");
    }
  }

  /**
   * Add to job form, sync mode: single job.
   */
  public function testAddToJobSingleSync() {
    if (!empty($this->smartlingPluginProviderSettings)) {
      $this->drupalPostForm('/admin/tmgmt/sources', [
        'items[1]' => 1,
      ], t('Request translation'));

      $this->drupalPostForm(NULL, [
        'target_language' => 'de',
        'settings[switcher]' => TMGMT_SMARTLING_ADD_TO_JOB,
        'settings[add_to_job_tab][container][job_info][due_date][date]' => '2020-12-12',
        'settings[add_to_job_tab][container][job_info][due_date][time]' => '12:12',
        'settings[add_to_job_tab][container][job_info][authorize]' => TRUE,
        'settings[smartling_users_time_zone]' => 'Europe/Kiev',
      ], t('Submit to provider'));

      $job = Job::load(1);

      $this->drupalGet('/admin/reports/dblog');
      $this->assertNoText(t('Smartling created a job'));
      $this->assertUniqueText(t('Smartling updated a job'));
      $this->assertUniqueText(t('Smartling created a batch'));
      $this->assertUniqueText(t('File uploaded. Job id: @job_id, file name: @filename.', [
        '@job_id' => $job->id(),
        '@filename' => $job->getTranslatorPlugin()->getFileName($job),
      ]));
      $this->assertNoText(t('Fallback: File uploaded. Job id: @job_id, file name: @filename.', [
        '@job_id' => $job->id(),
        '@filename' => $job->getTranslatorPlugin()->getFileName($job),
      ]));
      $this->assertUniqueText(t('Smartling executed a batch'));

      $this->assertTrue($this->getCountOfItemsInQueue('smartling_context_upload') == 1);
    }
    else {
      $this->fail("Smartling settings file for simpletests not found.");
    }
  }

  /**
   * Add to job form, sync mode: two jobs (queue mode).
   */
  public function testAddToJobQueueSync() {
    if (!empty($this->smartlingPluginProviderSettings)) {
      $this->drupalPostForm('/admin/tmgmt/sources', [
        'items[1]' => 1,
        'target_language' => '_all',
      ], t('Request translation'));

      $this->drupalPostForm(NULL, [
        'target_language' => 'de',
        'settings[switcher]' => TMGMT_SMARTLING_ADD_TO_JOB,
        'settings[add_to_job_tab][container][job_info][due_date][date]' => '2020-12-12',
        'settings[add_to_job_tab][container][job_info][due_date][time]' => '12:12',
        'settings[add_to_job_tab][container][job_info][authorize]' => TRUE,
        'settings[smartling_users_time_zone]' => 'Europe/Kiev',
      ], t('Submit to provider and continue'));

      $job1 = Job::load(1);
      $job2 = Job::load(2);

      $this->drupalGet('/admin/reports/dblog');
      $this->assertNoText(t('Smartling created a job'));
      $this->assertUniqueText(t('Smartling updated a job'));
      $this->assertUniqueText(t('Smartling created a batch'));
      $this->assertUniqueText(t('File uploaded. Job id: @job_id, file name: @filename.', [
        '@job_id' => $job1->id(),
        '@filename' => $job1->getTranslatorPlugin()->getFileName($job1),
      ]));
      $this->assertNoText(t('Fallback: File uploaded. Job id: @job_id, file name: @filename.', [
        '@job_id' => $job1->id(),
        '@filename' => $job1->getTranslatorPlugin()->getFileName($job1),
      ]));
      $this->assertUniqueText(t('File uploaded. Job id: @job_id, file name: @filename.', [
        '@job_id' => $job2->id(),
        '@filename' => $job2->getTranslatorPlugin()->getFileName($job2),
      ]));
      $this->assertNoText(t('Fallback: File uploaded. Job id: @job_id, file name: @filename.', [
        '@job_id' => $job2->id(),
        '@filename' => $job2->getTranslatorPlugin()->getFileName($job2),
      ]));
      $this->assertUniqueText(t('Smartling executed a batch'));

      $this->assertTrue($this->getCountOfItemsInQueue('smartling_context_upload') == 2);
    }
    else {
      $this->fail("Smartling settings file for simpletests not found.");
    }
  }

  /**
   * Add to job form, async mode: single job.
   */
  public function testAddToJobSingleAsync() {
    if (!empty($this->smartlingPluginProviderSettings)) {
      if (!empty($this->smartlingPluginProviderSettings)) {
        $this->drupalPostForm('/admin/tmgmt/translators/manage/smartling', [
          'settings[async_mode]' => TRUE,
        ], t('Save'));
      }

      $this->drupalPostForm('/admin/tmgmt/sources', [
        'items[1]' => 1,
      ], t('Request translation'));

      $this->drupalPostForm(NULL, [
        'target_language' => 'de',
        'settings[switcher]' => TMGMT_SMARTLING_ADD_TO_JOB,
        'settings[add_to_job_tab][container][job_info][due_date][date]' => '2020-12-12',
        'settings[add_to_job_tab][container][job_info][due_date][time]' => '12:12',
        'settings[add_to_job_tab][container][job_info][authorize]' => TRUE,
        'settings[smartling_users_time_zone]' => 'Europe/Kiev',
      ], t('Submit to provider'));

      $job = Job::load(1);

      $this->drupalGet('/admin/reports/dblog');
      $this->assertNoText(t('Smartling created a job'));
      $this->assertUniqueText(t('Smartling updated a job'));
      $this->assertUniqueText(t('Smartling created a batch'));
      $this->assertNoText(t('File uploaded. Job id: @job_id, file name: @filename.', [
        '@job_id' => $job->id(),
        '@filename' => $job->getTranslatorPlugin()->getFileName($job),
      ]));
      $this->assertNoText(t('Fallback: File uploaded. Job id: @job_id, file name: @filename.', [
        '@job_id' => $job->id(),
        '@filename' => $job->getTranslatorPlugin()->getFileName($job),
      ]));
      $this->assertNoText(t('Smartling executed a batch'));

      $this->assertTrue($this->getCountOfItemsInQueue('tmgmt_extension_suit_upload') == 1);
      $this->assertTrue($this->getCountOfItemsInQueue('smartling_context_upload') == 0);

      $this->processQueue('tmgmt_extension_suit_upload');

      $this->drupalGet('/admin/reports/dblog');
      $this->assertNoText(t('Smartling created a job'));
      $this->assertUniqueText(t('Smartling updated a job'));
      $this->assertUniqueText(t('Smartling created a batch'));
      $this->assertUniqueText(t('File uploaded. Job id: @job_id, file name: @filename.', [
        '@job_id' => $job->id(),
        '@filename' => $job->getTranslatorPlugin()->getFileName($job),
      ]));
      $this->assertNoText(t('Fallback: File uploaded. Job id: @job_id, file name: @filename.', [
        '@job_id' => $job->id(),
        '@filename' => $job->getTranslatorPlugin()->getFileName($job),
      ]));
      $this->assertUniqueText(t('Smartling executed a batch'));

      $this->assertTrue($this->getCountOfItemsInQueue('tmgmt_extension_suit_upload') == 0);
      $this->assertTrue($this->getCountOfItemsInQueue('smartling_context_upload') == 1);
    }
    else {
      $this->fail("Smartling settings file for simpletests not found.");
    }
  }

  /**
   * Add to job form, async mode: two jobs (queue mode).
   */
  public function testAddToJobQueueAsync() {
    if (!empty($this->smartlingPluginProviderSettings)) {
      if (!empty($this->smartlingPluginProviderSettings)) {
        $this->drupalPostForm('/admin/tmgmt/translators/manage/smartling', [
          'settings[async_mode]' => TRUE,
        ], t('Save'));
      }

      $this->drupalPostForm('/admin/tmgmt/sources', [
        'items[1]' => 1,
        'target_language' => '_all',
      ], t('Request translation'));

      $this->drupalPostForm(NULL, [
        'target_language' => 'de',
        'settings[switcher]' => TMGMT_SMARTLING_ADD_TO_JOB,
        'settings[add_to_job_tab][container][job_info][due_date][date]' => '2020-12-12',
        'settings[add_to_job_tab][container][job_info][due_date][time]' => '12:12',
        'settings[add_to_job_tab][container][job_info][authorize]' => TRUE,
        'settings[smartling_users_time_zone]' => 'Europe/Kiev',
      ], t('Submit to provider and continue'));

      $job1 = Job::load(1);
      $job2 = Job::load(2);

      $this->drupalGet('/admin/reports/dblog');
      $this->assertNoText(t('Smartling created a job'));
      $this->assertUniqueText(t('Smartling updated a job'));
      $this->assertUniqueText(t('Smartling created a batch'));
      $this->assertNoText(t('File uploaded. Job id: @job_id, file name: @filename.', [
        '@job_id' => $job1->id(),
        '@filename' => $job1->getTranslatorPlugin()->getFileName($job1),
      ]));
      $this->assertNoText(t('Fallback: File uploaded. Job id: @job_id, file name: @filename.', [
        '@job_id' => $job1->id(),
        '@filename' => $job1->getTranslatorPlugin()->getFileName($job1),
      ]));
      $this->assertNoText(t('File uploaded. Job id: @job_id, file name: @filename.', [
        '@job_id' => $job2->id(),
        '@filename' => $job2->getTranslatorPlugin()->getFileName($job2),
      ]));
      $this->assertNoText(t('Fallback: File uploaded. Job id: @job_id, file name: @filename.', [
        '@job_id' => $job2->id(),
        '@filename' => $job2->getTranslatorPlugin()->getFileName($job2),
      ]));
      $this->assertNoText(t('Smartling executed a batch'));

      $this->assertTrue($this->getCountOfItemsInQueue('tmgmt_extension_suit_upload') == 2);
      $this->assertTrue($this->getCountOfItemsInQueue('smartling_context_upload') == 0);

      $this->processQueue('tmgmt_extension_suit_upload');

      $this->drupalGet('/admin/reports/dblog');
      $this->assertNoText(t('Smartling created a job'));
      $this->assertUniqueText(t('Smartling updated a job'));
      $this->assertUniqueText(t('Smartling created a batch'));
      $this->assertUniqueText(t('File uploaded. Job id: @job_id, file name: @filename.', [
        '@job_id' => $job1->id(),
        '@filename' => $job1->getTranslatorPlugin()->getFileName($job1),
      ]));
      $this->assertNoText(t('Fallback: File uploaded. Job id: @job_id, file name: @filename.', [
        '@job_id' => $job1->id(),
        '@filename' => $job1->getTranslatorPlugin()->getFileName($job1),
      ]));
      $this->assertUniqueText(t('File uploaded. Job id: @job_id, file name: @filename.', [
        '@job_id' => $job2->id(),
        '@filename' => $job2->getTranslatorPlugin()->getFileName($job2),
      ]));
      $this->assertNoText(t('Fallback: File uploaded. Job id: @job_id, file name: @filename.', [
        '@job_id' => $job2->id(),
        '@filename' => $job2->getTranslatorPlugin()->getFileName($job2),
      ]));
      $this->assertUniqueText(t('Smartling executed a batch'));

      $this->assertTrue($this->getCountOfItemsInQueue('tmgmt_extension_suit_upload') == 0);
      $this->assertTrue($this->getCountOfItemsInQueue('smartling_context_upload') == 2);
    }
    else {
      $this->fail("Smartling settings file for simpletests not found.");
    }
  }

}
