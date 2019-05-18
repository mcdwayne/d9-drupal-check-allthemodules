<?php

namespace Drupal\akamai\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Event that is fired when Akamai header is formed.
 */
class AkamaiHeaderEvents extends Event {
  /**
   * The event dispatched when a response is received for a purge request.
   */
  const HEADER_CREATION = 'akamai.header_creation';

  /**
   * The tag array.
   *
   * @var array
   * The tags/urls/cp codes to manipulate for the header.
   */
  public $data;

  /**
   * Constructs the array.
   *
   * @param array $data
   *   The tags/urls/cp codes to manipulate for the header.
   */
  public function __construct(array $data) {
    $this->data = $data;
  }

}
