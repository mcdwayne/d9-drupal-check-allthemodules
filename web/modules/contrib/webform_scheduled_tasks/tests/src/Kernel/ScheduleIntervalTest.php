<?php

namespace Drupal\webform_scheduled_tasks\Kernel;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform_scheduled_tasks\Entity\WebformScheduledTask;

/**
 * Test the scheduling intervals.
 *
 * @group webform_scheduled_tasks
 */
class ScheduleIntervalTest extends KernelTestBase {

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

    $time = $this->prophesize(TimeInterface::class);
    $time->getRequestTime()->willReturn(1000000000);
    $this->container->set('datetime.time', $time->reveal());
  }

  /**
   * Test the interval scheduling.
   *
   * @dataProvider scheduleIntervalsTestCases
   */
  public function testScheduleIntervals($entity_values, $expected_next_run) {
    Webform::create(['id' => 'foo']);
    $scheduled_task = WebformScheduledTask::create([
      'id' => 'foo',
      'result_set_type' => 'all_submissions',
      'task_type' => 'test_task',
      'webform' => 'foo',
    ] + $entity_values);
    $scheduled_task->save();

    $this->assertEquals($entity_values['interval']['amount'], $scheduled_task->getRunIntervalAmount());
    $this->assertEquals($entity_values['interval']['multiplier'], $scheduled_task->getRunIntervalMultiplier());

    // The next run date will automatically be instantiated to the current time
    // plus the intervals specified.
    $this->assertEquals($expected_next_run, $scheduled_task->getNextTaskRunDate());
  }

  /**
   * Test cases for ::testScheduleIntervals.
   */
  public function scheduleIntervalsTestCases() {
    return [
      'Every day' => [
        [
          'interval' => [
            'amount' => 1,
            'multiplier' => 86400,
          ],
        ],
        1000086400,
      ],
      'Every 6 weeks' => [
        [
          'interval' => [
            'amount' => 6,
            'multiplier' => 604800,
          ],
        ],
        1003628800,
      ],
      'Every 42 minutes' => [
        [
          'interval' => [
            'amount' => 42,
            'multiplier' => 60,
          ],
        ],
        1000002520,
      ],
    ];
  }

  /**
   * Test manually setting an interval date.
   */
  public function testManuallySetIntervalDate() {
    $scheduled_task = WebformScheduledTask::create([
      'id' => 'foo',
      'result_set_type' => 'all_submissions',
      'task_type' => 'test_task',
      'interval' => [
        'amount' => 1,
        'multiplier' => 86400,
      ],
    ]);
    $scheduled_task->setNextTaskRunDate(10101010);
    $scheduled_task->save();

    $this->assertEquals(10101010, $scheduled_task->getNextTaskRunDate());
  }

}
