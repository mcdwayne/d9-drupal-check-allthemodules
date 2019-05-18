<?php

namespace Drupal\monitoring\Plugin\monitoring\SensorPlugin;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\monitoring\Result\SensorResultInterface;
use Drupal\monitoring\SensorPlugin\ExtendedInfoSensorPluginInterface;

/**
 * Monitors 404 error requests.
 *
 * @SensorPlugin(
 *   id = "redirect_404",
 *   provider = "redirect_404",
 *   label = @Translation("Redirect 404"),
 *   description = @Translation("Monitors the 404 error requests from the redirect_404 storage."),
 *   addable = FALSE
 * )
 */
class Redirect404SensorPlugin extends DatabaseAggregatorSensorPlugin implements ExtendedInfoSensorPluginInterface {

  /**
   * {@inheritdoc}
   */
  protected $configurableConditions = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $configurableVerboseOutput = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $configurableTable = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function addAggregateExpression(SelectInterface $select) {
    $select->addField('redirect_404', 'count', 'records_count');
  }

  /**
   * {@inheritdoc}
   */
  public function getAggregateQuery() {
    $query = parent::getAggregateQuery();
    $query->addField('redirect_404', 'path');
    // The message is the requested 404 URL.
    $query->condition('resolved', 0);
    $query->orderBy('count', 'DESC');
    $query->range(0, 1);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuery() {
    $query = parent::getQuery();
    // Unset timestamp order from parent class.
    $order = &$query->getOrderBy();
    $order = [];
    $query->orderBy('count', 'DESC');
    $query->condition('resolved', 0);
    $query->range(0, 10);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildTableHeader($rows = []) {
    $header = parent::buildTableHeader($rows);
    if (isset($header['path'])) {
      $header['path'] = $this->t('Path');
      $header['count'] = $this->t('Count');
      $header['timestamp'] = $this->t('Last access');
    }
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function runSensor(SensorResultInterface $result) {
    parent::runSensor($result);
    if (!empty($this->fetchedObject) && !empty($this->fetchedObject->path)) {
      $result->addStatusMessage($this->fetchedObject->path);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultConfiguration() {
    $default_config = [
      'settings' => [
        'table' => 'redirect_404',
      ],
    ];
    return $default_config;
  }

}
