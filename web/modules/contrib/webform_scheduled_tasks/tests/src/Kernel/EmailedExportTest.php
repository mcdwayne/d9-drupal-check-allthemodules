<?php

namespace Drupal\webform_scheduled_tasks\Kernel;

use Drupal\Core\Archiver\ArchiveTar;
use Drupal\Core\Test\AssertMailTrait;
use Drupal\file\Entity\File;
use Drupal\KernelTests\Core\File\FileTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform_scheduled_tasks\Entity\WebformScheduledTask;
use Drupal\webform_scheduled_tasks\Plugin\WebformScheduledTasks\Task\EmailedExport;

/**
 * Test the email export plugin.
 *
 * @group webform_scheduled_tasks
 */
class EmailedExportTest extends FileTestBase {

  use AssertMailTrait;

  /**
   * Disable strict config schema.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'user',
    'file',
    'webform',
    'webform_scheduled_tasks',
    'webform_scheduled_tasks_test_types',
  ];

  /**
   * A test webform.
   *
   * @var \Drupal\webform\WebformInterface
   */
  protected $testWebform;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('webform', ['webform']);
    $this->installSchema('file', ['file_usage']);
    $this->installEntitySchema('webform_submission');
    $this->installEntitySchema('file');
    $this->installEntitySchema('user');

    $this->setSetting('file_private_path', $this->container->get('site.path') . '/private');
    $this->testWebform = $this->createTestWebform();
  }

  /**
   * Test exporting submissions to the file system.
   */
  public function testFileSystemExport() {
    $scheduled_task = $this->createTestTask([
      'email_addresses' => 'foo@example.com, bar@example.com',
      'storage_type' => EmailedExport::STORAGE_TYPE_FILESYSTEM,
      'exporter' => 'delimited_text',
      'exporter_settings' => [
        'delimiter' => '|',
        'excel' => TRUE,
      ],
      'include_attachments' => FALSE,
      'delete_submissions' => FALSE,
    ]);
    $this->createTestSubmissions();
    webform_scheduled_tasks_cron();

    $mail = $this->getMails();

    // Two emails will be sent with a link to the private file.
    $this->assertCount(2, $mail);
    $this->assertEquals($mail[0]['to'], 'foo@example.com');
    $this->assertEquals($mail[1]['to'], 'bar@example.com');
    $this->assertContains('system/files/scheduled-exports/foo.webform_scheduled_task.foo.csv', $mail[0]['body']);
    $this->assertEquals($mail[0]['subject'], 'Export generated for Test form');

    // The file itself should contain the test submission data, with a pipe
    // separated format.
    $this->assertFileExists('private://scheduled-exports/foo.webform_scheduled_task.foo.csv');
    $file_contents = file_get_contents('private://scheduled-exports/foo.webform_scheduled_task.foo.csv');
    $this->assertContains('|"FOO SUBMISSION CONTENT"', $file_contents);
    $this->assertContains('|"BAR SUBMISSION CONTENT"', $file_contents);

    /** @var \Drupal\file_entity\FileEntityInterface $file */
    $file = File::load(3);
    $this->assertEquals(TRUE, $file->isPermanent());
    $this->assertEquals('private://scheduled-exports/foo.webform_scheduled_task.foo.csv', $file->getFileUri());
    $this->assertCount(1, $this->container->get('file.usage')->listUsage($file));

    // Assert the second run of the exporter creates a unique file.
    $scheduled_task->setNextTaskRunDate(1);
    webform_scheduled_tasks_cron();
    $file = File::load(4);
    $this->assertEquals('private://scheduled-exports/foo.webform_scheduled_task.foo_0.csv', $file->getFileUri());
    $this->assertCount(1, $this->container->get('file.usage')->listUsage($file));
  }

  /**
   * Test submissions are retained by default.
   */
  public function testExportRetainedSubmissions() {
    $this->createTestTask([
      'email_addresses' => 'foo@example.com, bar@example.com',
      'storage_type' => EmailedExport::STORAGE_TYPE_FILESYSTEM,
      'exporter' => 'delimited_text',
      'exporter_settings' => [
        'delimiter' => '|',
        'excel' => TRUE,
      ],
      'include_attachments' => FALSE,
    ]);
    $submissions = $this->createTestSubmissions();
    webform_scheduled_tasks_cron();
    $this->assertCount(2, $this->getMails());
    foreach ($submissions as $submission) {
      $this->assertNotNull(WebformSubmission::load($submission->id()));
    }
  }

  /**
   * Test deleting submissions after an export.
   */
  public function testExportSubmissionsDelete() {
    $this->createTestTask([
      'email_addresses' => 'foo@example.com, bar@example.com',
      'storage_type' => EmailedExport::STORAGE_TYPE_FILESYSTEM,
      'exporter' => 'delimited_text',
      'exporter_settings' => [
        'delimiter' => '|',
        'excel' => TRUE,
      ],
      'delete_submissions' => TRUE,
      'include_attachments' => FALSE,
    ]);
    $submissions = $this->createTestSubmissions();
    webform_scheduled_tasks_cron();

    $this->assertCount(2, $this->getMails());

    foreach ($submissions as $submission) {
      $this->assertNull(WebformSubmission::load($submission->id()));
    }
  }

  /**
   * Test bad exports do not result in lost files.
   */
  public function testBadExportsAreNotCompleted() {
    // Make the task fail by setting the private filesystem somewhere not
    // writeable.
    $this->setSetting('file_private_path', '/not-a-writeable-dir');
    $task = $this->createTestTask([
      'email_addresses' => 'foo@example.com, bar@example.com',
      'storage_type' => EmailedExport::STORAGE_TYPE_FILESYSTEM,
      'exporter' => 'delimited_text',
      'exporter_settings' => [
        'delimiter' => '|',
        'excel' => TRUE,
      ],
      'delete_submissions' => TRUE,
      'include_attachments' => FALSE,
    ]);
    $submissions = $this->createTestSubmissions();
    webform_scheduled_tasks_cron();

    $this->assertTrue($task->isHalted());
    $this->assertEquals('An error was encountered when running the task: Could not create a directory for the exported files to be written to.', $task->getHaltedReason());

    foreach ($submissions as $submission) {
      $this->assertNotNull(WebformSubmission::load($submission->id()));
    }
  }

  /**
   * Test an archive based export.
   */
  public function testArchiveBasedExport() {
    $task = $this->createTestTask([
      'email_addresses' => 'foo@example.com, bar@example.com',
      'storage_type' => EmailedExport::STORAGE_TYPE_FILESYSTEM,
      'exporter' => 'json',
      'exporter_settings' => [
        'file_name' => 'submission-[webform_submission:serial]',
      ],
      'delete_submissions' => TRUE,
      'include_attachments' => FALSE,
    ]);

    $this->createTestSubmissions();
    webform_scheduled_tasks_cron();

    $this->assertFalse($task->isHalted());

    $file = File::load(3);
    $this->assertEquals('private://scheduled-exports/foo.webform_scheduled_task.foo.tar.gz', $file->getFileUri());

    // Smoke test we are calling the right methods to correctly generate an
    // archive with content from the actual submission.
    $archive = new ArchiveTar('private://scheduled-exports/foo.webform_scheduled_task.foo.tar.gz');
    $this->assertEquals('submission-1.json', $archive->listContent()[0]['filename']);
    $this->assertEquals('submission-2.json', $archive->listContent()[1]['filename']);
    $this->assertContains('FOO SUBMISSION CONTENT', $archive->extractInString('submission-1.json'));
    $this->assertContains('BAR SUBMISSION CONTENT', $archive->extractInString('submission-2.json'));
  }

  /**
   * Test exporting submissions exceeding the batch limit.
   */
  public function testSubmissionsExceedingBatchLimit() {
    // Set a low batch limit. This currently only affects a UI decision the user
    // must make, so the exporter that runs with chunked submission loads on a
    // long-running cron run shouldn't run into the same limitations.
    $this->container->get('config.factory')->getEditable('webform.settings')->set('batch.default_batch_export_size', 2);
    $this->createTestTask([
      'email_addresses' => 'foo@example.com, bar@example.com',
      'storage_type' => EmailedExport::STORAGE_TYPE_FILESYSTEM,
      'exporter' => 'json',
      'exporter_settings' => [
        'file_name' => 'submission-[webform_submission:serial]',
      ],
      'delete_submissions' => TRUE,
      'include_attachments' => FALSE,
    ]);

    $this->createTestSubmissions();
    $this->createTestSubmissions();

    webform_scheduled_tasks_cron();

    // 4 files will exist from the 4 submissions, the 5th will be the export.
    $file = File::load(5);
    $this->assertEquals('private://scheduled-exports/foo.webform_scheduled_task.foo.tar.gz', $file->getFileUri());

    $archive = new ArchiveTar('private://scheduled-exports/foo.webform_scheduled_task.foo.tar.gz');
    $this->assertCount(4, $archive->listContent());
    $this->assertContains('FOO SUBMISSION CONTENT', $archive->extractInString('submission-1.json'));
    $this->assertContains('BAR SUBMISSION CONTENT', $archive->extractInString('submission-2.json'));
    $this->assertContains('FOO SUBMISSION CONTENT', $archive->extractInString('submission-3.json'));
    $this->assertContains('BAR SUBMISSION CONTENT', $archive->extractInString('submission-4.json'));
  }

  /**
   * Test the task with an empty result set.
   */
  public function testTaskWithEmptyResultSet() {
    $task = $this->createTestTask([
      'email_addresses' => 'foo@example.com, bar@example.com',
      'storage_type' => EmailedExport::STORAGE_TYPE_FILESYSTEM,
      'exporter' => 'json',
      'exporter_settings' => [
        'file_name' => 'submission-[webform_submission:serial]',
      ],
      'delete_submissions' => TRUE,
      'include_attachments' => FALSE,
    ]);
    webform_scheduled_tasks_cron();

    $this->assertFalse($task->isHalted());
    $this->assertCount(0, $this->getMails());
  }

  /**
   * Test exporting archived files with a format that is already an archive.
   */
  public function testIncludeAttachedFilesWithNativeArchive() {
    $task = $this->createTestTask([
      'email_addresses' => 'foo@example.com, bar@example.com',
      'storage_type' => EmailedExport::STORAGE_TYPE_FILESYSTEM,
      'exporter' => 'json',
      'exporter_settings' => [
        'file_name' => 'submission-[webform_submission:serial]',
      ],
      'delete_submissions' => TRUE,
      'include_attachments' => TRUE,
    ]);

    $this->createTestSubmissions();
    webform_scheduled_tasks_cron();

    $this->assertFalse($task->isHalted());
    $this->assertCount(2, $this->getMails());

    $archive = new ArchiveTar('private://scheduled-exports/foo.webform_scheduled_task.foo.tar.gz');
    $contents = $archive->listContent();
    $this->assertCount(4, $contents);
    $this->assertEquals('submission-1/test.pdf', $contents[0]['filename']);
    $this->assertEquals('submission-1.json', $contents[1]['filename']);
    $this->assertEquals('submission-2/test.pdf', $contents[2]['filename']);
    $this->assertEquals('submission-2.json', $contents[3]['filename']);
  }

  /**
   * Test exporting archived files with a format that is a file.
   */
  public function testIncludeAttachedFilesWithNativeFile() {
    $task = $this->createTestTask([
      'email_addresses' => 'foo@example.com, bar@example.com',
      'storage_type' => EmailedExport::STORAGE_TYPE_FILESYSTEM,
      'exporter' => 'delimited_text',
      'exporter_settings' => [
        'delimiter' => '|',
        'excel' => TRUE,
      ],
      'delete_submissions' => TRUE,
      'include_attachments' => TRUE,
    ]);

    $this->createTestSubmissions();
    webform_scheduled_tasks_cron();

    $this->assertFalse($task->isHalted());
    $this->assertCount(2, $this->getMails());

    // Ensure we get an archive containing the delimited export plus the two
    // test files for each submission.
    $archive = new ArchiveTar('private://scheduled-exports/foo.webform_scheduled_task.foo.tar.gz');
    $contents = $archive->listContent();
    $this->assertCount(3, $contents);
    $this->assertEquals('submission-1/test.pdf', $contents[0]['filename']);
    $this->assertEquals('submission-2/test.pdf', $contents[1]['filename']);
    $this->assertEquals('foo.webform_scheduled_task.foo/foo.webform_scheduled_task.foo.csv', $contents[2]['filename']);
    $this->assertContains('FOO SUBMISSION CONTENT', $archive->extractInString('foo.webform_scheduled_task.foo/foo.webform_scheduled_task.foo.csv'));
    $this->assertContains('BAR SUBMISSION CONTENT', $archive->extractInString('foo.webform_scheduled_task.foo/foo.webform_scheduled_task.foo.csv'));
  }

  /**
   * Create test submissions.
   */
  protected function createTestSubmissions() {
    $submissions = [];
    foreach (['FOO SUBMISSION CONTENT', 'BAR SUBMISSION CONTENT'] as $submission_content) {
      $test_file = $this->container->get('plugin.manager.webform.element')->createInstance('managed_file')->getTestValues([
        '#webform_key' => 'test',
        '#file_extensions' => 'pdf',
      ], $this->testWebform, []);
      $submission = WebformSubmission::create([
        'webform_id' => 'foo',
        'data' => [
          'name' => $submission_content,
          'test_file' => array_shift($test_file),
        ],
      ]);
      $submission->save();
      $submissions[] = $submission;
    }
    return $submissions;
  }

  /**
   * Create a test webform.
   *
   * @param array $values
   *   Values to save with the webform.
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\webform\Entity\Webform
   *   A test webform.
   */
  protected function createTestWebform(array $values = []) {
    $webform = Webform::create($values + [
      'id' => 'foo',
      'title' => 'Test form',
    ]);
    $webform->save();
    $elements = [
      'name' => [
        '#type' => 'textfield',
        '#title' => 'name',
      ],
      'test_file' => [
        '#type' => 'managed_file',
        '#title' => 'Important file',
      ],
    ];
    $webform->setElements($elements);
    $webform->save();
    return $webform;
  }

  /**
   * Create a test scheduled task.
   *
   * @param array $settings
   *   Settings for the task plugin.
   *
   * @return \Drupal\webform_scheduled_tasks\Entity\WebformScheduledTaskInterface
   *   A scheduled task.
   */
  protected function createTestTask(array $settings = []) {
    $scheduled_task = WebformScheduledTask::create([
      'id' => 'foo',
      'result_set_type' => 'all_submissions',
      'webform' => 'foo',
      'interval' => ['amount' => 1, 'multiplier' => 60],
      'task_type' => 'export_email_results',
      'task_settings' => $settings,
    ]);
    $scheduled_task->save();
    $scheduled_task->setNextTaskRunDate(10);
    return $scheduled_task;
  }

}
