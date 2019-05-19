<?php

namespace Drupal\silktide\event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class SilktideEvent.
 *
 * @package Drupal\silktide\event
 */
class SilktideEvent extends Event {

  /**
   * Name of the event fired when we need to notify Silktide.
   *
   * @Event
   *
   * @var string
   */
  const EVENT_NAME = 'silktide.event';

  /**
   * The URL we are storing.
   *
   * @var string
   */
  private $url;

  /**
   * SilktideEvent constructor.
   *
   * @param string $url
   *   The URL of the event.
   */
  public function __construct($url) {
    $this->url = $url;
  }

  /**
   * Gets the URL.
   *
   * @return string
   *   The url.
   */
  public function getUrl() {
    return $this->url;
  }

}
