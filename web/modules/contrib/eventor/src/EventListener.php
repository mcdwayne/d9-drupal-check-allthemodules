<?php

namespace Drupal\eventor;

use Stringy\Stringy as S;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class EventListener.
 *
 * All custom event listeners must extend this class.
 */
abstract class EventListener implements EventSubscriberInterface {

  const EVENT_METHOD_PREFIX = 'when';

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];

    foreach (self::getEventMethods() as $method) {
      $events[self::getEventName($method)][] = [$method];
    }

    return $events;
  }

  /**
   * List of method names following the "when{EventName}" convention.
   *
   * @return array
   *   Method names.
   */
  protected static function getEventMethods() {
    $methods = [];
    $oClass = new \ReflectionClass(get_called_class());
    foreach ($oClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
      if (S::create($method->name)->startsWith(self::EVENT_METHOD_PREFIX)) {
        $methods[] = $method->name;
      }
    }

    return $methods;
  }

  /**
   * Event name based on method name.
   *
   * @param string $method
   *   Event listener method.
   *
   * @return string
   *   Event name.
   */
  protected static function getEventName($method) {
    return S::create($method)
      ->removeLeft(self::EVENT_METHOD_PREFIX)
      ->underscored()
      ->__toString();
  }

}
