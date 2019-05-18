<?php

namespace Drupal\Tests\cron_service\Traits;

use Drupal\Core\Queue\DatabaseQueue;
use Drupal\Core\Queue\QueueFactory;

/**
 * Trait for mocking a queue. Can be used in function testing.
 *
 * @codeCoverageIgnore
 */
trait QueueMockTrait {

  /**
   * Creates a mocked queue.
   *
   * @param string $name
   *   Queue name for queue factory. Leave empty if don't care.
   * @param bool $updateContainer
   *   If true the 'queue' service will be replaced by the mocked queue factory.
   * @param string $class
   *   Queue class/interface to mock.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject
   *   Mocked queue.
   */
  protected function mockQueue(string $name = '', bool $updateContainer = TRUE, $class = DatabaseQueue::class) {
    $queue = $this->getMockBuilder($class)
      ->disableOriginalConstructor()
      ->getMock();

    $method = $this
      ->mockQueueFactory($updateContainer)
      ->method('get');
    if ($name) {
      $method->with($name);
    }
    $method->willReturn($queue);

    return $queue;
  }

  /**
   * Creates a mocked queue factory.
   *
   * @param bool $updateContainer
   *   If true the 'queue' service will be replaced by the mocked queue factory.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject
   *   Mocked queue factory.
   */
  protected function mockQueueFactory(bool $updateContainer = TRUE) {
    /** @var \PHPUnit\Framework\MockObject\MockObject $queueFactory */
    $queueFactory = $this->getMockBuilder(QueueFactory::class)
      ->disableOriginalConstructor()
      ->getMock();
    if ($updateContainer) {
      $this->container->set('queue', $queueFactory);
    }
    return $queueFactory;
  }

}
