<?php

namespace Drupal\jms_serializer;

use JMS\Serializer\Handler\SubscribingHandlerInterface;

/**
 * Class HandlerCollector.
 *
 * @package Drupal\jms_serializer
 */
class HandlerCollector {

  /**
   * The handlers.
   *
   * @var array
   */
  protected $handlers = [];

  /**
   * @param \JMS\Serializer\Handler\SubscribingHandlerInterface $handler
   *   The handler to add.
   */
  public function addHandler(SubscribingHandlerInterface $handler) {
    $this->handlers[] = $handler;
  }

  /**
   * Get the list of collected handlers.
   *
   * @return SubscribingHandlerInterface[]
   *   The collected handlers.
   */
  public function getHandlers() {
    return $this->handlers;
  }

}
