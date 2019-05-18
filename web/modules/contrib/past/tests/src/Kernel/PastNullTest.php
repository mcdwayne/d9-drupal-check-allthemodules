<?php

namespace Drupal\Tests\past\Kernel;

use Drupal\past\PastEventNull;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the Past null implementation.
 *
 * @group past
 */
class PastNullTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  public static $modules = ['past'];

  /**
   * Tests that past_event_create() returns PastEventUll when misconfigured.
   */
  public function testMissingBackend() {
    $event = past_event_create('past', 'test', 'A test log entry');
    $this->assertTrue($event instanceof PastEventNull);

    // Make sure that fluent calls are supported.
    $event->setParentEventId('')
    ->setSeverity(-1)
    ->setSessionId('')
    ->setMessage('')
    ->setTimestamp(-1)
    ->setMachineName('')
    ->setUid(-1);

    $array_argument = ['data' => ['sub' => 'value'], 'something' => 'else'];
    $event->addArgument('first', $array_argument);
    $event->addArgument('second', 'simple');

    $event->addArgument('third', 'chaining')
    ->getKey();
  }
}
