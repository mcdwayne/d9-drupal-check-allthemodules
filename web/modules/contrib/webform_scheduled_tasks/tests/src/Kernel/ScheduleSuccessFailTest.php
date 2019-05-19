<?php

namespace Drupal\webform_scheduled_tasks\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform_scheduled_tasks\Entity\WebformScheduledTask;

/**
 * Test the schedule integration with plugins during success/fail.
 *
 * @group webform_scheduled_tasks
 */
class ScheduleSuccessFailTest extends KernelTestBase {

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
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('webform', ['webform']);
    $this->installEntitySchema('webform_submission');
    $this->installEntitySchema('user');

    $this->messenger = $this->container->get('messenger');
  }

  /**
   * Test registering the status of a run with plugins.
   */
  public function testRegisteringStatusWithPlugins() {
    Webform::create(['id' => 'foo']);

    $scheduled_task = WebformScheduledTask::create([
      'id' => 'foo',
      'result_set_type' => 'test_result_set',
      'task_type' => 'test_task',
      'webform' => 'foo',
    ]);

    $scheduled_task->registerSuccessfulTask();

    $this->assertEquals([
      'Run test_result_set ::onSuccess',
      'Run test_task ::onSuccess',
    ], $this->messenger->all()['status']);

    $this->messenger->deleteAll();
    $scheduled_task->registerFailedTask();

    $this->assertEquals([
      'Run test_result_set ::onFailure',
      'Run test_task ::onFailure',
    ], $this->messenger->all()['status']);
  }

}
