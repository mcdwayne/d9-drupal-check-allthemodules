<?php

namespace Drupal\simple_global_filter\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Description of UserSelectsEvent
 *
 */
class UserSelectsEvent extends Event{

  /**
   * The name (id) of the global filter.
   * @var string
   */
  protected $global_filter_id;

  /**
   * Constructs the event
   * @param string $global_filter_id
   */
  public function __construct($global_filter_id) {
    $this->global_filter_id = $global_filter_id;
  }

  /**
   * Returns the global filter name.
   */
  public function getGlobalFilter() {
    return $this->global_filter_id;
  }
}
