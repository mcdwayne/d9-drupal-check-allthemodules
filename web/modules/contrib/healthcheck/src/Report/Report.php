<?php


namespace Drupal\healthcheck\Report;

use Drupal\healthcheck\Finding\FindingInterface;
use Drupal\healthcheck\Finding\FindingStatus;

class Report implements ReportInterface, \Countable, \IteratorAggregate {

  /**
   * The findings retained in this collection.
   *
   * @var array
   */
  protected $findings;

  /**
   * {@inheritdoc}
   */
  public function addFinding(FindingInterface $finding) {
    $key = $finding->getKey();
    $this->findings[$key] = $finding;
  }

  /**
   * {@inheritdoc}
   */
  public function addFindings($findings) {
    foreach ($findings as $finding) {
      $this->addFinding($finding);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return count($this->findings);
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    return new \ArrayIterator($this->findings);
  }

  /**
   * {@inheritdoc}
   */
  public function getFindingsByStatus() {
    $results = [];

    /** @var \Drupal\healthcheck\Finding\FindingInterface $finding */
    foreach ($this->findings as $finding) {
      $key = $finding->getStatus();

      $results[$key][] = $finding;
    }

    krsort($results);

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function getFindingsByCheck() {
    $results = [];

    /** @var \Drupal\healthcheck\Finding\FindingInterface $finding */
    foreach ($this->findings as $finding) {
      $check = $finding->getCheck();
      $check_id = $check->getPluginId();

      $results[$check_id][] = $finding;
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function getHighestStatus() {
    // Get all the findings by status.
    $findings_by_status = $this->getFindingsByStatus();

    // Sort them by key (the status) in descending order.
    krsort($findings_by_status);

    // Reset the array's internal pointer.
    reset($findings_by_status);

    // Return the first key.
    return key($findings_by_status);
  }

  /**
   * {@inheritdoc}
   */
  public function getCountsByStatus() {
    // Get all the possible statues.
    $statuses = FindingStatus::getAsArray();

    // Get all the findings by status.
    $findings = $this->getFindingsByStatus();

    // Collate the counts by status.
    $results = [];
    foreach ($statuses as $status) {
      $results[$status] = empty($findings[$status]) ? 0 : count($findings[$status]);
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() {
    $out = [];

    /** @var \Drupal\healthcheck\Finding\FindingInterface $finding */
    foreach ($this->findings as $finding) {
      $out[] = $finding->toArray();
    }

    return $out;
  }
}
