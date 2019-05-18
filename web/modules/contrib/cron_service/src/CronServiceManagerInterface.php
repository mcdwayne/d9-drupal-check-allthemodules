<?php

namespace Drupal\cron_service;

/**
 * Cron services manager.
 *
 * Collects all the services with 'cron_service' tag, checks their scheduled
 * time to execute and executes all that should be executed now.
 *
 * @package Drupal\cron_service
 */
interface CronServiceManagerInterface {

  /**
   * Executes handler by id.
   *
   * Checks if the handler with given id exists, if it should be executed now
   * (unless $force is set to TRUE) and executes it. Returns TRUE if execution
   * was performed, FALSE otherwise. The result of the execution is not
   * considered.
   *
   * @param string $id
   *   Handler (service) id.
   * @param bool $force
   *   Ignore execution schedule check.
   *
   * @return bool
   *   True if the handler was executed.
   */
  public function executeHandler(string $id, $force = FALSE): bool;

  /**
   * Executes all the handlers.
   */
  public function execute();

  /**
   * Adds service to services list.
   *
   * @param \Drupal\cron_service\CronServiceInterface $instance
   *   Service to add.
   * @param string $id
   *   Service id.
   */
  public function addHandler(CronServiceInterface $instance, string $id);

  /**
   * Returns next execution time.
   *
   * @param string $id
   *   Handler Id.
   *
   * @return int
   *   Unix timestamp.
   */
  public function getScheduledCronRunTime(string $id): int;

  /**
   * Returns true if the service can be executed.
   *
   * @param string $id
   *   Handler id.
   *
   * @return bool
   *   TRUE if it's time to run.
   */
  public function shouldRunNow(string $id): bool;

  /**
   * Sets to force next execution of the service.
   *
   * It doesn't immediately executes the service but it forces to bypass all the
   * schedule checks on the next run.
   *
   * @param string $id
   *   Service id.
   */
  public function forceNextExecution(string $id);

}
