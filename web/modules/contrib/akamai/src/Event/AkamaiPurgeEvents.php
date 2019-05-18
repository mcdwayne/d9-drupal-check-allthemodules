<?php

namespace Drupal\akamai\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Event that is fired when Akamai purge is formed.
 */
class AkamaiPurgeEvents extends Event {

  const PURGE_CREATION = 'akamai.purge_creation';

  /**
   * The invalidation array.
   *
   * @var array
   * The tags/urls/cp codes to manipulate before purge.
   */
  public $data;

  /**
   * Constructs the array.
   *
   * @param array $data
   *   The tags/urls/cp codes to manipulate before purge.
   */
  public function __construct(array $data) {
    $this->data = $data;
  }

}
