<?php

namespace Drupal\Tests\webform_scheduled_tasks\Kernel;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform_scheduled_tasks\Entity\WebformScheduledTask;

/**
 * Submissions since last success result set plugin test.
 *
 * @coversDefaultClass \Drupal\webform_scheduled_tasks\Plugin\WebformScheduledTasks\ResultSet\SubmissionsCompletedSinceLastSuccess
 * @group webform_scheduled_tasks
 */
class SubmissionsCompletedSinceLastSuccessTest extends KernelTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'user',
    'webform',
    'webform_scheduled_tasks',
    'webform_scheduled_tasks_test_types',
  ];

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * A test scheduled task.
   *
   * @var \Drupal\webform_scheduled_tasks\Entity\WebformScheduledTaskInterface
   */
  protected $schedule;

  /**
   * The current time.
   *
   * @var int
   */
  protected $currentTime = 1000000000;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('webform', ['webform']);
    $this->installEntitySchema('webform_submission');
    $this->installEntitySchema('user');

    $current_time = &$this->currentTime;
    $this->time = $this->prophesize(TimeInterface::class);
    $this->time->getRequestTime()->will(function () use (&$current_time) {
      return $current_time;
    });
    $this->container->set('datetime.time', $this->time->reveal());

    $schedule_webform = Webform::create([
      'id' => 'scheduled_webform',
    ]);
    $schedule_webform->save();
    $this->schedule = WebformScheduledTask::create([
      'id' => 'test_task',
      'webform' => $schedule_webform->id(),
      'result_set_type' => 'submissions_completed_since_last_success',
      'task_type' => 'test_task',
    ]);
    $this->schedule->save();
  }

  /**
   * @covers ::getResultSet
   */
  public function testBasicIncrementalSubmissions() {
    $published_submissions = [
      $this->createTestSubmission(),
      $this->createTestSubmission(),
      $this->createTestSubmission(),
    ];
    $draft_submission = $this->createTestSubmission([
      'in_draft' => TRUE,
    ]);

    $this->timePasses();
    $this->assertSubmissionResults($published_submissions);
    $this->schedule->registerSuccessfulTask();

    $new_submissions = [
      $this->createTestSubmission(),
      $this->createTestSubmission(),
      $this->createTestSubmission(),
    ];

    $this->timePasses();
    $this->assertSubmissionResults($new_submissions);
    $this->schedule->registerSuccessfulTask();

    // Draft submissions created previously, will be included in the list of
    // submissions once they are out of draft status.
    $draft_submission->in_draft = FALSE;
    $draft_submission->save();

    $this->timePasses();
    $this->assertSubmissionResults([$draft_submission]);
    $this->schedule->registerSuccessfulTask();
  }

  /**
   * @covers ::getResultSet
   */
  public function testSubmissionCreationTimeScheduleRaceConditions() {
    // @codingStandardsIgnoreStart
    // In the following timeline, the plugin should select submission b to c,
    // but exclude d, since it was created on or after the thread started
    // running. The next run will capture d and e, since the recorded time for a
    // successful run is the request time:
    // (sub a) (last completed) (sub c) (request time) (sub e) (execute)
    //           (sub b)                    (sub d)
    // The "last completed" in the diagram will be primed to the current time.
    // @codingStandardsIgnoreEnd
    $this->assertSubmissionResults([]);
    $this->schedule->registerSuccessfulTask();
    // Submission B will be created at exactly the same time that the last task
    // ran.
    $submission_b = $this->createTestSubmission([]);

    $this->timePasses();

    $submission_c = $this->createTestSubmission([]);

    $this->timePasses();

    // Submission D happens at the exact time the plugin is executing for a
    // second time.
    $submission_d = $this->createTestSubmission([]);
    // Submission E happens between the request time and the time the plugin
    // takes to execute. Simulate this by pitching submission e, 1 second into
    // the future.
    $submission_e = $this->createTestSubmission([
      'completed' => $this->currentTime + 1,
    ]);
    $this->assertSubmissionResults([
      $submission_b,
      $submission_c,
    ]);
    $this->schedule->registerSuccessfulTask();

    // Elapse two seconds, one of the simulated second of execution that
    // submission e used and one second between running intervals of the next
    // scheduled job.
    $this->timePasses();
    $this->timePasses();

    $this->assertSubmissionResults([
      $submission_d,
      $submission_e,
    ]);
  }

  /**
   * @covers ::getResultSet
   */
  public function testAllResultsAreIncludedForFailedTask() {
    $submissions = [
      $this->createTestSubmission(),
      $this->createTestSubmission(),
      $this->createTestSubmission(),
    ];
    $this->assertSubmissionResults($submissions);
    $this->schedule->registerFailedTask();

    $this->assertSubmissionResults($submissions);
    $this->schedule->registerFailedTask();
  }

  /**
   * Simulate time passing.
   */
  protected function timePasses() {
    $this->currentTime++;
  }

  /**
   * Create a test submission.
   */
  protected function createTestSubmission($fields = []) {
    $submission = WebformSubmission::create($fields + [
      'webform_id' => 'scheduled_webform',
    ]);
    $submission->save();
    return $submission;
  }

  /**
   * Assert the expected submissions in a schedule result set.
   */
  protected function assertSubmissionResults($expectedSubmissions) {
    $this->schedule = WebformScheduledTask::load($this->schedule->id());
    $results = iterator_to_array($this->schedule->getResultSetPlugin()->getResultSet());
    $entity_id = function (EntityInterface $entity) {
      return $entity->id();
    };
    $this->assertEquals(array_map($entity_id, $expectedSubmissions), array_map($entity_id, $results));
  }

}
