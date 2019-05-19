<?php

namespace Drupal\Tests\sir_trevor\Unit\TestDoubles;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class EventDispatcherDummy
 *
 * @package Drupal\sir_trevor\Tests\Unit\TestDoubles
 */
class EventDispatcherDummy implements EventDispatcherInterface {

  /**
   * {@inheritdoc}
   */
  public function dispatch($eventName, Event $event = NULL) {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function addListener($eventName, $listener, $priority = 0) {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function addSubscriber(EventSubscriberInterface $subscriber) {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function removeListener($eventName, $listener) {
    // Intentionally left empty. Dummies don't do anything.
  }
  
  /**
   * {@inheritdoc}
   */
  public function removeSubscriber(EventSubscriberInterface $subscriber) {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function getListeners($eventName = NULL) {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function hasListeners($eventName = NULL) {
    // Intentionally left empty. Dummies don't do anything.
  }

}
