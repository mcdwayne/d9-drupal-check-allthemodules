<?php

namespace Drupal\healthcheck_historical;

use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\healthcheck\Finding\FindingInterface;
use Drupal\healthcheck\Report\ReportInterface;

/**
 * Class HistoricalService.
 */
class HistoricalService implements HistoricalServiceInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The datetime service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $datetime;

  /**
   * Constructs a new HistoricalService object.
   */
  public function __construct(Connection $database,
                              ConfigFactoryInterface $config_factory,
                              TimeInterface $datetime) {
    $this->database = $database;
    $this->configFactory = $config_factory;
    $this->datetime = $datetime;
  }

  /**
   * {@inheritdoc}
   */
  public function saveReport(ReportInterface $report) {
    // Save the report.
    $query = $this->database->insert('healthcheck_report')
      ->fields([
        'site' => \Drupal::request()->getHost(),
        'created' => $this->datetime->getRequestTime(),
      ]);

    // Execute the query, get back the report ID.
    $report_id = $query->execute();

    // Now save each finding.
    /** @var \Drupal\healthcheck\Finding\FindingInterface $finding */
    foreach ($report as $finding) {
      $this->saveFinding($report_id, $finding);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function cron() {
    // Get the configuration store.
    $config = $this->configFactory->get('healthcheck_historical.settings');

    $keep_for = $config->get('keep_reports_for');

    if ($keep_for > 0) {
      $request_time = $this->datetime->getRequestTime();
      $purge_time = $request_time - $keep_for;

      // Find reports older than clear date.
      $query = $this->database->select('healthcheck_report', 'hr')
        ->fields('hr', [
          'id',
        ]);

      $query->condition('hr.created', $purge_time, '<');

      $result = $query->execute()->fetchCol();

      // Delete finding rows with those report IDs.
      $this->deleteReports($result);
    }
  }

  /**
   * Save a finding.
   *
   * @param int $report_id
   *   The report ID.
   * @param \Drupal\healthcheck\Finding\FindingInterface $finding
   *   The finding to save.
   *
   * @return int|null
   *   The finding ID, if any.
   *
   * @throws \Exception
   */
  protected function saveFinding($report_id, FindingInterface $finding) {
    $query = $this->database->insert('healthcheck_finding')
      ->fields([
        'report_id' => $report_id,
        'finding_key' => $finding->getKey(),
        'status' => $finding->getStatus(),
        'created' => $this->datetime->getRequestTime(),
        'label' => $finding->getLabel(),
        'message' => $finding->getMessage(),
        'data' => serialize($finding->getAllData()),
      ]);

    return $query->execute();
  }

  /**
   * Deletes reports with the given IDs.
   *
   * @param $report_ids
   *   An array of report IDs to delete.
   */
  protected function deleteReports(&$report_ids) {
    $query = $this->database->delete('healthcheck_report');

    $query->condition('id', $report_ids, 'IN');

    $query->execute();

    // Delete the report's findings too.
    $this->deleteFindings($report_ids);
  }

  /**
   * Deletes findings for the given report IDs.
   *
   * @param array $report_ids
   *   A list of report IDs for which to delete findings.
   */
  protected function deleteFindings(array &$report_ids) {
    $query = $this->database->delete('healthcheck_finding');

    $query->condition('report_id', $report_ids, 'IN');

    $query->execute();
  }
}
