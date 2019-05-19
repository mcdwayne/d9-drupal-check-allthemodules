<?php

namespace Drupal\single_page_site\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class EventSinglePageSiteAlterOutput.
 *
 * @package Drupal\single_page_site\Event
 */
class EventSinglePageSiteAlterOutput extends Event {

  protected $output;
  protected $currentItemCount;

  /**
   * EventAlterOutput constructor.
   *
   * @param mixed $output
   *   Output value.
   * @param int $current_item_count
   *   Current item count.
   */
  public function __construct($output, $current_item_count) {
    $this->output = $output;
    $this->currentItemCount = $current_item_count;
  }

  /**
   * Function to get output.
   *
   * @return mixed
   *   Returns the output value.
   */
  public function getOutput() {
    return $this->output;
  }

  /**
   * Function to set output.
   *
   * @param mixed $output
   *   Output value.
   */
  public function setOutput($output) {
    $this->output = $output;
  }

  /**
   * Function to Get Current Item Count.
   *
   * @return mixed
   *   Returns the Current Item Count.
   */
  public function getCurrentItemCount() {
    return $this->currentItemCount;
  }

  /**
   * Function to Set Current Item Count.
   *
   * @param mixed $currentItemCount
   *   Current Item Count value.
   */
  public function setCurrentItemCount($currentItemCount) {
    $this->currentItemCount = $currentItemCount;
  }

}
