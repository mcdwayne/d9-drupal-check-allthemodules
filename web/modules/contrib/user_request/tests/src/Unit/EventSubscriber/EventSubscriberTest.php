<?php

namespace Drupal\Tests\user_request\Unit\EventSubscriber;

use Drupal\Tests\user_request\Unit\UnitTestCase;

/**
 * Base class for event subscriber tests.
 *
 * @group user_request
 */
abstract class EventSubscriberTest extends UnitTestCase {

  /**
   * The event subscriber being tested.
   *
   * @var \Symfony\Component\EventDispatcher\EventSubscriberInterface
   */
  protected $eventSubscriber;

  protected function mockTransitionEvent($transition, $request) {
    $event = $this->getMockBuilder('\Drupal\state_machine\Event\WorkflowTransitionEvent')
      ->disableOriginalConstructor()
      ->getMock();
    $event
      ->expects($this->any())
      ->method('getTransition')
      ->will($this->returnValue($transition));
    $event
      ->expects($this->any())
      ->method('getEntity')
      ->will($this->returnValue($request));
    return $event;
  }

}
