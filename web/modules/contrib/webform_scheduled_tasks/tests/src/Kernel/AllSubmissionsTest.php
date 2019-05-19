<?php

namespace Drupal\Tests\webform_scheduled_tasks\Kernel;

use Drupal\Core\Entity\EntityInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform_scheduled_tasks\Entity\WebformScheduledTask;

/**
 * @coversDefaultClass \Drupal\webform_scheduled_tasks\Plugin\WebformScheduledTasks\ResultSet\AllSubmissions
 * @group webform_scheduled_tasks
 */
class AllSubmissionsTest extends KernelTestBase {

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
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('webform', ['webform']);
    $this->installEntitySchema('webform_submission');
    $this->installEntitySchema('user');
  }

  /**
   * @covers ::getResultSet
   */
  public function testGetResultSet() {
    $schedule_webform = Webform::create([
      'id' => 'scheduled_webform',
    ]);
    $schedule_webform->save();

    $unrelated_webform = Webform::create([
      'id' => 'unrelated_form',
    ]);
    $unrelated_webform->save();

    $schedule = WebformScheduledTask::create([
      'id' => 'test_task',
      'webform' => $schedule_webform->id(),
      'result_set_type' => 'all_submissions',
      'task_type' => 'test_task',
    ]);
    $schedule->save();

    $test_submissions = [];
    foreach (range(0, 5) as $i) {
      $submission = WebformSubmission::create([
        'webform_id' => $schedule_webform->id(),
      ]);
      $submission->save();
      $test_submissions[] = $submission;
    }

    $draft_submission = WebformSubmission::create([
      'webform_id' => $schedule_webform->id(),
      'in_draft' => TRUE,
    ]);
    $draft_submission->save();

    $unrelated_submission = WebformSubmission::create([
      'webform_id' => $unrelated_webform->id(),
    ]);
    $unrelated_submission->save();

    $results = iterator_to_array($schedule->getResultSetPlugin()->getResultSet($schedule_webform));

    $entity_id = function (EntityInterface $entity) {
      return $entity->id();
    };
    $this->assertEquals(array_map($entity_id, $test_submissions), array_map($entity_id, $results));
  }

}
