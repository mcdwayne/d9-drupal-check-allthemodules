<?php

namespace Drupal\sa11y\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * A new Sa11yStartedEvent.
 *
 * @see \Drupal\sa11y\Events\Sa11yEvents::STARTED
 */
class Sa11yStartedEvent extends Event {

  /**
   * The report ID of the started job.
   *
   * @var int
   */
  protected $id;

  /**
   * Creates a new Sa11yStartedEvent.
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
