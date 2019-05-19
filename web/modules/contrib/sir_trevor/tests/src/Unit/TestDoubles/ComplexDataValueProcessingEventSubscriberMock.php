<?php

namespace Drupal\Tests\sir_trevor\Unit\TestDoubles;

use Drupal\sir_trevor\ComplexDataValueProcessingEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ComplexDataValueProcessingEventSubscriberMock
 *
 * @package Drupal\Tests\sir_trevor\Unit\TestDoubles
 */
class ComplexDataValueProcessingEventSubscriberMock implements EventSubscriberInterface {
  /** @var array */
  private $replacementResults = [];

  /**
   * Returns an array of event names this subscriber wants to listen to.
   *
   * The array keys are event names and the value can be:
   *
   *  * The method name to call (priority defaults to 0)
   *  * An array composed of the method name to call and the priority
   *  * An array of arrays composed of the method names to call and respective
   *    priorities, or 0 if unset
   *
   * For instance:
   *
   *  * array('eventName' => 'methodName')
   *  * array('eventName' => array('methodName', $priority))
   *  * array('eventName' => array(array('methodName1', $priority), array('methodName2')))
   *
   * @return array The event names to listen to
   */
  public static function getSubscribedEvents() {
    return [];
  }

  /**
   * @param \Drupal\sir_trevor\ComplexDataValueProcessingEvent $event
   */
  public function processEvent(ComplexDataValueProcessingEvent $event) {
    if (isset($this->replacementResults[$event->getType()])) {
      $event->setReplacementValue($this->replacementResults[$event->getType()]);
    }
  }

  /**
   * @param string $type
   * @param mixed $result
   */
  public function setReplacementResultForType($type, $result) {
    $this->replacementResults[$type] = $result;
  }
}
