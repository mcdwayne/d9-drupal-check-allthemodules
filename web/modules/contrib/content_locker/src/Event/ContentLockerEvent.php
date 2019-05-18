<?php

namespace Drupal\content_locker\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class ContentLockerEvent.
 *
 * @package Drupal\content_locker\Event
 */
class ContentLockerEvent extends Event {

  const ENTITY_NODE_VIEW = 'content_locker.view';

  /**
   * Plugin type.
   *
   * @var string
   */
  protected $pluginType;

  /**
   * Constructs a content locker event.
   */
  public function __construct($pluginType) {
    $this->pluginType = $pluginType;
  }

  /**
   * Returns the current plugin type.
   */
  public function getPluginType() {
    return $this->pluginType;
  }

}
