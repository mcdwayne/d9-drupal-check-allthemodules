<?php

namespace Drupal\monitoring\Plugin\monitoring\SensorPlugin;

use Drupal\Core\Link;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\monitoring\Result\SensorResultInterface;
use Drupal\monitoring\SensorPlugin\ExtendedInfoSensorPluginInterface;
use Drupal\monitoring\SensorPlugin\SensorPluginBase;
use Drupal\ultimate_cron\Entity\CronJob;

/**
 * Monitors the ultimate cron errors.
 *
 * @SensorPlugin(
 *   id = "ultimate_cron_errors",
 *   provider = "ultimate_cron",
 *   label = @Translation("Ultimate cron errors"),
 *   description = @Translation("Provides insight on the cron processes."),
 *   addable = FALSE
 * )
 */
class UltimateCronErrorsSensorPlugin extends SensorPluginBase implements ExtendedInfoSensorPluginInterface {

  /**
   * The error log entries.
   *
   * @var array
   */
  protected $logEntries;

  /**
   * {@inheritdoc}
   */
  public function runSensor(SensorResultInterface $result) {
    $this->logEntries = $this->getErrorLogEntries();
    $result->setValue(count($this->logEntries));
  }

  /**
   * {@inheritdoc}
   */
  public function resultVerbose(SensorResultInterface $result) {
    $rows = [];
    foreach ($this->logEntries as $log_entry) {
      $rows[] = [
        'name' => $log_entry->job->label() . ' (' . $log_entry->job->getModuleName() . ')',
        'message' => strip_tags($log_entry->message),
        'logs' => Link::createFromRoute($this->t('View logs'), 'entity.ultimate_cron_job.logs', ['ultimate_cron_job' => $log_entry->name]),
      ];
    }
    $header = [
      'name' => $this->t('Cron job'),
      'message' => $this->t('Message'),
      'logs' => $this->t('Logs'),
    ];
    $output['log_entries'] = [
      '#type' => 'verbose_table_result',
      '#title' => $this->t('Log entries'),
      '#header' => $header,
      '#rows' => $rows,
    ];
    return $output;
  }

  /**
   * Returns error log entries.
   *
   * @return array
   *   An array of log entries.
   */
  public function getErrorLogEntries() {
    // Loads active cron jobs.
    $job_ids = \Drupal::entityQuery('ultimate_cron_job')
      ->condition('status', TRUE)
      ->execute();
    $jobs = CronJob::loadMultiple($job_ids);

    $log_entries = [];
    /** @var \Drupal\ultimate_cron\Entity\CronJob $job */
    foreach ($jobs as $job) {
      $job_entries = $job->getLogEntries(ULTIMATE_CRON_LOG_TYPE_ALL, 10);
      foreach ($job_entries as $job_entry) {
        if ($job_entry->severity == RfcLogLevel::ERROR) {
          $job_entry->job = $job;
          $log_entries[] = $job_entry;
        }
      }
    }

    return $log_entries;
  }
}
