<?php

namespace Drupal\sa11y;

use Drupal\node\NodeInterface;

/**
 * Fetches accessibility reports from a remote API.
 */
interface Sa11yInterface {

  /**
   * Report has been created, pending response.
   */
  const CREATED = 0;

  /**
   * Positive Response received from API.
   */
  const RUNNING = 1;

  /**
   * There was a failure from the API.
   */
  const ERROR = 2;

  /**
   * No response was received.
   */
  const TIMEOUT = 3;

  /**
   * Report was successfully received.
   */
  const COMPLETE = 4;

  /**
   * Report was cancelled.
   */
  const CANCELLED = 5;

  /**
   * Get a report by ID.
   *
   * @param int $reportId
   *   The report id.
   *
   * @return mixed
   *   FALSE or the full report object.
   */
  public function getReport($reportId);

  /**
   * Get Violations by report and optionally filter by path.
   *
   * @param int $reportId
   *   The report id to filter by.
   * @param string $url
   *   The optional URL to filter by.
   *
   * @return mixed
   *   The full report data object or an empty array.
   */
  public function getViolations($reportId, $url = NULL);

  /**
   * Get any pending reports.
   *
   * @return mixed
   *   FALSE or the full pending report object.
   */
  public function getPending();

  /**
   * Creates a report if none are already pending.
   *
   * @param bool $single
   *   Is the report a single URL.
   * @param array $options
   *   Additional options to send.
   *
   * @return mixed
   *   FALSE or the ID of the created report.
   */
  public function createReport($single = FALSE, array $options = []);

  /**
   * Processes any new jobs with the API.
   */
  public function processPending();

  /**
   * Gets the latest report by a URL alias.
   *
   * @param string $url
   *   The url to check for.
   *
   * @return mixed
   *   A report object or FALSE.
   */
  public function getLatestByUrl($url);

    /**
   * Finds a the latest report based on a node object.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node object.
   *
   * @return bool|mixed
   *   A report object or FALSE.
   */
  public function getLatestByNode(NodeInterface $node);

  /**
   * Sends off a report to the api.
   *
   * @param object $pending
   *   The pending report to send.
   *
   * @return mixed
   *   A json response or FALSE.
   */
  public function send($pending);

  /**
   * Receive a report.
   *
   * Recieves a POST request from the API with the information about
   * the completed job. If completed the report (CSV) will be parsed
   * into the database.
   */
  public function receive();

}
