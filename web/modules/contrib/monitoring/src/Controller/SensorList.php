<?php

namespace Drupal\monitoring\Controller;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Controller\ControllerBase;
use Drupal\monitoring\Result\SensorResultInterface;
use Drupal\monitoring\Sensor\SensorManager;
use Drupal\monitoring\SensorRunner;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SensorList extends ControllerBase {

  /**
   * The sensor runner.
   *
   * @var \Drupal\monitoring\SensorRunner
   */
  protected $sensorRunner;

  /**
   * The sensor manager.
   *
   * @var \Drupal\monitoring\Sensor\SensorManager
   */
  protected $sensorManager;

  /**
   * Constructs a \Drupal\monitoring\Form\SensorDetailForm object.
   *
   * @param \Drupal\monitoring\SensorRunner $sensor_runner
   *   The factory for configuration objects.
   * @param \Drupal\monitoring\Sensor\SensorManager $sensor_manager
   *   The sensor manager service.
   */
  public function __construct(SensorRunner $sensor_runner, SensorManager $sensor_manager) {
    $this->sensorRunner = $sensor_runner;
    $this->sensorManager = $sensor_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('monitoring.sensor_runner'),
      $container->get('monitoring.sensor_manager')
    );
  }

  public function content() {
    $rows = array();
    $results = $this->sensorRunner->runSensors();
    $status_overview = array(
      SensorResultInterface::STATUS_OK => 0,
      SensorResultInterface::STATUS_INFO => 0,
      SensorResultInterface::STATUS_WARNING => 0,
      SensorResultInterface::STATUS_CRITICAL => 0,
      SensorResultInterface::STATUS_UNKNOWN => 0,
    );
    $total_execution_time = 0;
    $non_cached_execution_time = 0;
    // Oldest sensor age in seconds.
    $oldest_sensor_age = 0;
    // Oldest sensor config.
    $oldest_sensor_config = NULL;

    foreach ($this->sensorManager->getSensorConfigByCategories() as $category => $category_sensor_config) {

      // Category grouping row.
      $rows[] = array(
        'data' => array(
          'label' => array(
            'data' => array('#markup' => '<h3>' . $category . '</h3>'),
            'colspan' => 7
          ),
        ),
      );
      $ok_row_count = 0;

      /** @var \Drupal\monitoring\SensorConfigInterface $sensor_config */
      foreach ($category_sensor_config as $sensor_name => $sensor_config) {
        if (!isset($results[$sensor_name])) {
          continue;
        }
        /** @var \Drupal\monitoring\Result\SensorResultInterface $sensor_result */
        $sensor_result = $results[$sensor_name];
        $called_before = REQUEST_TIME - $sensor_result->getTimestamp();
        if ($called_before > $oldest_sensor_age) {
          $oldest_sensor_config = $sensor_config;
          $oldest_sensor_age = $called_before;
        }

        $row['data']['label']['data'] = array('#markup' => '<span title="' . $sensor_config->getDescription() . '">' . $sensor_config->getLabel() . '</span>');

        $row['data']['sensor_status'] = array(
          'data' => $sensor_result->getStatus(),
          'class' => array('status'),
        );

        $row['data']['timestamp'] = \Drupal::service('date.formatter')->formatInterval(REQUEST_TIME - $sensor_result->getTimestamp());
        $row['data']['execution_time'] = array(
          'data' => $sensor_result->getExecutionTime() . 'ms',
          'class' => array('execution-time'),
        );
        $row['data']['sensor_status_message'] = Unicode::truncate(strip_tags($sensor_result->getMessage()), 200, TRUE, TRUE);

        $row['class'] = array('monitoring-' . strtolower($sensor_result->getStatus()));

        $links = array();
        $links['details'] = array(
          'title' => t('Details'),
          'url' => $sensor_config->urlInfo('details-form')
        );

        // Display a force execution link for any sensor that can be cached.
        if ($sensor_config->getCachingTime() && $this->currentUser()->hasPermission('monitoring force run')) {
          $links['force_execution'] = array(
            'title' => t('Force execution'),
            'url' => $sensor_config->urlInfo('force-run-sensor')
          );
        }
        $links['edit'] = array(
          'title' => t('Edit'),
          'url' => $sensor_config->urlInfo('edit-form'),
          'query' => array('destination' => 'admin/reports/monitoring')
        );

        \Drupal::moduleHandler()->alter('monitoring_sensor_links', $links, $sensor_config);

        $row['data']['actions'] = array();
        if (!empty($links)) {
          $row['data']['actions']['data'] = array('#type' => 'dropbutton', '#links' => $links);
        }

        $rows[] = $row;

        $status_overview[$sensor_result->getStatus()]++;
        $total_execution_time += $sensor_result->getExecutionTime();
        if (!$sensor_result->isCached()) {
          $non_cached_execution_time += $sensor_result->getExecutionTime();
        }
        if ($sensor_result->getStatus() == SensorResultInterface::STATUS_OK) {
          $ok_row_count++;
        }
        else {
          $ok_row_count = -1;
        }
      }

      // Add special class if all sensors of a category are ok.
      if ($ok_row_count >= 0) {
        $index = count($rows) - $ok_row_count - 1;
        $rows[$index]['class'][] = 'sensor-category-ok';
      }
    }

    $output['summary'] = array(
      '#theme' => 'monitoring_overview_summary',
      '#status_overview' => $status_overview,
      '#total_execution_time' => $total_execution_time,
      '#non_cached_execution_time' => $non_cached_execution_time,
    );

    // We can add the oldest_sensor_* data only if there are sensor results cached.
    if (!empty($oldest_sensor_config)) {
      $output['summary']['#oldest_sensor_label'] = $oldest_sensor_config->getLabel();
      $output['summary']['#oldest_sensor_category'] = $oldest_sensor_config->getCategory();
      $output['summary']['#oldest_sensor_called_before'] = \Drupal::service('date.formatter')->formatInterval($oldest_sensor_age);
    }

    $header = array(
      t('Sensor name'),
      array('data' => t('Status'), 'class' => array('status')),
      t('Called before'),
      t('Execution time'),
      t('Status Message'),
      array('data' => t('Actions'), 'class' => array('actions')),
    );

    $monitoring_escalated_sensors = $status_overview[SensorResultInterface::STATUS_WARNING] +
        $status_overview[SensorResultInterface::STATUS_CRITICAL] +
        $status_overview[SensorResultInterface::STATUS_UNKNOWN];

    $output['table'] = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#sticky' => TRUE,
      '#attributes' => array(
        'class' => array('monitoring-severity-colors'),
        'id' => 'monitoring-sensors-overview',
      ),
      '#attached' => [
        'drupalSettings' => [
          'monitoring_escalated_sensors' => $monitoring_escalated_sensors,
        ],
        'library' => ['monitoring/monitoring'],
      ],
    );

    return $output;
  }
}
