<?php


namespace Drupal\healthcheck\Event;


interface HealthcheckEventInterface {

  /**
   * Gets the event code.
   *
   * @return string
   *   The event code.
   *
   * @see \Drupal\healthcheck\Event\HealthcheckEvents
   */
  public function getEvent();

  /**
   * Gets the report that triggered this event.
   *
   * @return \Drupal\healthcheck\Report\Report
   *   The report.
   */
  public function getReport();

}
