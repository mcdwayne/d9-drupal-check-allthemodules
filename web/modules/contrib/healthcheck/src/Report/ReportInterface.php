<?php

namespace Drupal\healthcheck\Report;

use Drupal\healthcheck\Finding\FindingInterface;

/**
 * A report is a collection of Findings.
 */
interface ReportInterface {

  /**
   * Add a Finding to the report.
   *
   * @param \Drupal\healthcheck\Finding\FindingInterface $finding
   *   The Finding to add to the collection.
   */
  public function addFinding(FindingInterface $finding);


  /**
   * Add multiple findings to the report.
   *
   * @param array $findings
   *   An array of Finding objects.
   */
  public function addFindings($findings);

  /**
   * Get the Findings keyed by status.
   *
   * @return array
   *   Get the findings keyed by FindingStatus.
   *
   * @see \Drupal\healthcheck\Finding\FindingStatus
   */
  public function getFindingsByStatus();

  /**
   * Gets an array of Findings keyed by check.
   *
   * @return array
   *   An array of findings keyed by their check's plugin ID.
   */
  public function getFindingsByCheck();

  /**
   * Get the highest status in the report.
   *
   * @return int
   *   The highest FindingStatus in the collection.
   *
   * @see \Drupal\healthcheck\Finding\FindingStatus
   */
  public function getHighestStatus();

  /**
   * Get a count of findings by status.
   *
   * @return array
   *   Get a count of findings by status.
   */
  public function getCountsByStatus();

  /**
   * Returns the collection as an associative array.
   *
   * @return array
   *   The collection as an array
   */
  public function toArray();

}
