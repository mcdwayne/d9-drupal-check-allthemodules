<?php

namespace Drupal\sa11y\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * A new Sa11yCompletedEvent.
 *
 * @see \Drupal\sa11y\Event\Sa11yEvents::COMPLETED
 */
class Sa11yCompletedEvent extends Event {

  /**
   * The report ID of the completed job.
   *
   * @var int
   */
  protected $id;

  /**
   * Creates a new Sa11yCompletedEvent.
   *
   * @param int $id
   *   The report id.
   */
  public function __construct($id) {
    $this->id = $id;
  }

  /**
   * Returns the report ID for this event.
   *
   * @return int
   *   The report ID.
   */
  public function getId() {
    return $this->id;
  }

}
