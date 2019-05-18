<?php

namespace Drupal\external_entities\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Defines an external entity raw data mapping event.
 */
class ExternalEntityMapRawDataEvent extends Event {

  /**
   * The raw data.
   *
   * @var array
   */
  protected $rawData;

  /**
   * Constructs a map raw data event object.
   *
   * @param array $raw_data
   *   The raw data being mapped.
   */
  public function __construct(array $raw_data) {
    $this->rawData = $raw_data;
  }

  /**
   * Gets the raw data being mapped.
   *
   * @return array
   *   The raw data.
   */
  public function getRawData() {
    return $this->rawData;
  }

  /**
   * Sets the raw data being mapped.
   *
   * @param array
   *   The raw data.
   */
  public function setRawData($raw_data) {
    $this->rawData = $raw_data;
  }

}
