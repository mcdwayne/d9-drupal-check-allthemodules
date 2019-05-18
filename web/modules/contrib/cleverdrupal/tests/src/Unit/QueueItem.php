<?php

namespace Drupal\Tests\cleverreach\Unit;

use CleverReach\Infrastructure\Interfaces\Required\TaskQueueStorage;
use CleverReach\Infrastructure\ServiceRegister;
use Drupal\KernelTests\KernelTestBase;

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class QueueItem extends KernelTestBase {
  public static $modules = ['cleverreach'];

  /**
   * TestServiceRegistered.
   */
  public function testServiceRegistered() {
    try {
      $this->getService();
      $exception = NULL;
    }
    catch (\InvalidArgumentException $exception) {
    }

    $this->assertSame($exception, NULL, 'Service is not registered.');
  }

  /**
   * TestTaskQueueStorageService.
   */
  public function testTaskQueueStorageService() {
    $service = $this->getService();
    $queueStorageTest = new BaseQueueItem($service);
    $exception = NULL;
    try {
      $queueStorageTest->test();
    }
    catch (\Exception $exception) {
      $this->fail('TaskQueueStorageService must pass all the checking.' . $exception->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema(
        'cleverreach',
        [
          'cleverreach_process',
          'cleverreach_queue',
        ]
    );
  }

  /**
   * GetService.
   */
  private function getService() {
    return ServiceRegister::getService(TaskQueueStorage::CLASS_NAME);
  }

}
