<?php
/**
 * @file
 * Contains \Drupal\monitoring\Plugin\monitoring\SensorPlugin\UpdateStatusSensorPlugin.
 */

namespace Drupal\monitoring\Plugin\monitoring\SensorPlugin;

use Drupal\monitoring\Result\SensorResultInterface;
use Drupal\monitoring\SensorPlugin\SensorPluginBase;
use Drupal\update\UpdateFetcherInterface;
use Drupal\update\UpdateManagerInterface;

/**
 * Monitors for available updates of Drupal core and installed contrib modules.
 *
 * @SensorPlugin(
 *   id = "update_status",
 *   label = @Translation("Update status"),
 *   description = @Translation("Monitors for available updates of Drupal core and installed contrib modules."),
 *   addable = FALSE,
 *   provider = "update"
 * )
 *
 * Based on drupal core update module.
 */
class UpdateStatusSensorPlugin extends SensorPluginBase {

  /**
   * {@inheritdoc}
   */
  protected $configurableValueType = FALSE;

  /**
   * {@inheritdoc}
   */
  public function runSensor(SensorResultInterface $result) {
    $type = $this->sensorConfig->getSetting('type');
    $status = $this->calculateStatus($type);

    $result->setStatus($status);

    $available = update_get_available();
    $project_data = update_calculate_project_data($available);

    if ($type == 'core') {
      $this->checkCore($result, $project_data);
    }
    else {
      $this->checkContrib($result, $project_data);
    }
  }

  /**
   * Checks core status and sets sensor status message.
   *
   * @param \Drupal\monitoring\Result\SensorResultInterface $result
   * @param array $project_data
   */
  protected function checkCore(SensorResultInterface $result, $project_data) {
    $info = $project_data['drupal'];
    $status = $this->getStatusText($info['status']);

    if ($status == 'unknown') {
      $result->addStatusMessage('Core update status unknown');
      // Do not escalate in case the status is unknown.
      $result->setStatus(SensorResultInterface::STATUS_INFO);
    }
    elseif ($status == 'current') {
      $result->addStatusMessage('Core up to date');
    }
    else {
      $result->addStatusMessage('Core (@current) - @status - latest @latest', array(
        '@status' => $status,
        '@current' => isset($info['existing_version']) ? $info['existing_version'] : NULL,
        '@latest' => isset($info['latest_version']) ? $info['latest_version'] : NULL,
      ));
    }
  }

  /**
   * Checks contrib status and sets sensor status message.
   *
   * @param \Drupal\monitoring\Result\SensorResultInterface $result
   * @param array $project_data
   */
  protected function checkContrib(SensorResultInterface $result, $project_data) {

    unset($project_data['drupal']);

    $updates = [];

    foreach ($project_data as $info) {
      $status_text = $this->getStatusText($info['status']);
      if (!isset($updates[$status_text])) {
        $updates[$status_text] = 0;
      }
      $updates[$status_text]++;
    }

    foreach ($updates as $status_text => $count) {
      $result->addStatusMessage($count . ' ' . $status_text);
    }
  }

  /**
   * Gets status text.
   *
   * @param int $status
   *   One of UpdateManagerInterface::* constants.
   *
   * @return string
   *   Status text.
   */
  protected function getStatusText($status) {
    switch ($status) {
      case UpdateManagerInterface::NOT_SECURE:
        return 'NOT SECURE';
        break;

      case UpdateManagerInterface::CURRENT:
        return 'current';
        break;

      case UpdateManagerInterface::REVOKED:
        return 'version revoked';
        break;

      case UpdateManagerInterface::NOT_SUPPORTED:
        return 'not supported';
        break;

      case UpdateManagerInterface::NOT_CURRENT:
        return 'update available';
        break;

      case UpdateFetcherInterface::UNKNOWN:
      case UpdateFetcherInterface::NOT_CHECKED:
      case UpdateFetcherInterface::NOT_FETCHED:
      case UpdateFetcherInterface::FETCH_PENDING:
        return 'unknown';
        break;
    }
  }

  /**
   * Executes the update requirements hook and calculates the status for it.
   *
   * @param string $type
   *   Which types of updates to check for, core or contrib.
   *
   * @return string
   *   One of the SensorResultInterface status constants.
   */
  protected function calculateStatus($type) {
    \Drupal::service('module_handler')->loadInclude('update', 'install');

    $requirements = update_requirements('runtime');

    $update_info = array();
    if (isset($requirements['update_' . $type])) {
      $update_info = $requirements['update_' . $type];
    }
    $update_info += array(
      'severity' => REQUIREMENT_OK,
    );

    if ($update_info['severity'] == REQUIREMENT_OK) {
      return SensorResultInterface::STATUS_OK;
    }
    elseif ($update_info['severity'] == REQUIREMENT_INFO) {
      return SensorResultInterface::STATUS_INFO;
    }
    // If the level is warning, which is updates available, we do not need to
    // escalate.
    elseif ($update_info['severity'] == REQUIREMENT_WARNING) {
      return SensorResultInterface::STATUS_INFO;
    }
    else {
      return SensorResultInterface::STATUS_CRITICAL;
    }
  }

}
