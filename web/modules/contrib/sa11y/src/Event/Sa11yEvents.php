<?php

namespace Drupal\sa11y\Event;

/**
 * Contains all events thrown by Sa11y.
 */
final class Sa11yEvents {

  /**
   * The STARTED event occurs when a job has been initiated by the API.
   *
   * @var string
   */
  const STARTED = 'sa11y.started';

  /**
   * Completed Event.
   *
   * The COMPLETED event occurs when a report has been received back
   * from the API and successfully parsed into the database.
   *
   * @var string
   */
  const COMPLETED = 'sa11y.completed';

}
