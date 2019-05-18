<?php

namespace Drupal\Tests\eventor\Unit;

use Drupal\eventor\Service\EventDispatcher;
use Drupal\eventor_test\Events\DeathStarWasDestroyed;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class EventDispatcherTest.
 *
 * @package Drupal\Tests\eventor\Unit
 *
 * @group eventor
 */
class EventDispatcherTest extends UnitTestCase {

  /**
   * Mock for EventDispatcherInterface.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject
   *
   * @see \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $systemDispatcher;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->systemDispatcher = $this->getMock(EventDispatcherInterface::class);
  }

  /**
   * Ensures that the event is dispatched with an expected name.
   */
  public function testEventIsDispatchedWithCorrectName() {
    $event = new DeathStarWasDestroyed();
    $this->setExpectedEvent('death_star_was_destroyed', $event);

    $dispatcher = new EventDispatcher($this->systemDispatcher);
    $dispatcher->dispatch($event);

    $this->verifyMockObjects();
  }

  /**
   * Sets an event name expectation on the event dispatcher.
   *
   * @param string $name
   *   Event name.
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   Event object.
   */
  protected function setExpectedEvent($name, Event $event) {
    $this->systemDispatcher->expects($this->once())->method('dispatch')->with($name, $event);
  }

}
