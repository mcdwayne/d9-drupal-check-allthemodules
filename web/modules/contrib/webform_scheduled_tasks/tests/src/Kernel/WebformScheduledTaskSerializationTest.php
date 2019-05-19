<?php

namespace Drupal\webform_scheduled_tasks\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform_scheduled_tasks\Entity\WebformScheduledTask;

/**
 * Test serializing the entity.
 *
 * @group webform_scheduled_tasks
 */
class WebformScheduledTaskSerializationTest extends KernelTestBase {

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
   * Test serializing the entity.
   */
  public function testSerialize() {
    Webform::create(['id' => 'foo']);
    $scheduled_task = WebformScheduledTask::create([
      'id' => 'foo',
      'result_set_type' => 'all_submissions',
      'task_type' => 'test_task',
      'webform' => 'foo',
    ]);
    $scheduled_task->save();

    $serialized = serialize($scheduled_task);
    $unserialized = unserialize($serialized);

    $this->assertEquals($scheduled_task->id(), $unserialized->id());
  }

}
