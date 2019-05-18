<?php

namespace Drupal\Tests\eventor\Kernel;

use Drupal\eventor_test\Events\DeathStarWasDestroyed;
use Drupal\KernelTests\KernelTestBase;

/**
 * Class EventorTest.
 *
 * @package Drupal\Tests\eventor\Kernel
 *
 * @group eventor
 */
class EventorTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'eventor',
    'eventor_test',
  ];

  /**
   * Tests that custom events are handled by custom event listeners.
   */
  public function testEventListenerReactsToEvent() {
    $event = new DeathStarWasDestroyed();

    /** @var \Drupal\eventor\Service\EventDispatcher $dispatcher */
    $dispatcher = \Drupal::service('eventor.event_dispatcher');
    $dispatcher->dispatch($event);

    $this->assertSame(FALSE, $event->darthVaderIsHappy);
    $this->assertSame(TRUE, $event->lukeIsHappy);
  }

}
