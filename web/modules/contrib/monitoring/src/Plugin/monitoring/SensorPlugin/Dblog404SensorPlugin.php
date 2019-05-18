<?php
/**
 * @file
 * Contains \Drupal\monitoring\Plugin\monitoring\SensorPlugin\Dblog404SensorPlugin.
 */

namespace Drupal\monitoring\Plugin\monitoring\SensorPlugin;

use Drupal\monitoring\Result\SensorResultInterface;

/**
 * Monitors 404 page errors from dblog.
 *
 * @SensorPlugin(
 *   id = "dblog_404",
 *   provider = "dblog",
 *   label = @Translation("404 page errors (database log)"),
 *   description = @Translation("Monitors 404 page errors from database log."),
 *   addable = FALSE
 * )
 *
 * Displays URL with highest occurrence as message.
 */
class Dblog404SensorPlugin extends WatchdogAggregatorSensorPlugin {

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
  public function getAggregateQuery() {
    $query = parent::getAggregateQuery();
    $query->addField('watchdog', 'location');
    // The message is the requested 404 URL.
    $query->groupBy('location');
    $query->orderBy('records_count', 'DESC');
    $query->range(0, 1);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuery() {
    $query = parent::getQuery();
    $this->addAggregateExpression($query);
    $query->groupBy('location');
    // Get just the max timestamp, drop the rest.
    $fields = &$query->getFields();
    unset($fields['timestamp']);
    $query->addExpression('MAX(timestamp)', 'timestamp');

    // Drop the existing order, order by record count instead.
    $order = &$query->getOrderBy();
    $order = [];
    $query->orderBy('records_count', 'DESC');
    $query->range(0, 20);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildTableHeader($rows = []) {
    $header = parent::buildTableHeader($rows);
    if (isset($header['location'])) {
      // A static override of the labels.
      // OK because sensor does not support configurability / instances.
      $header['location'] = $this->t('Path');
      $header['records_count'] = $this->t('Count');
      $header['timestamp'] = $this->t('Last access');
    }
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function runSensor(SensorResultInterface $result) {
    parent::runSensor($result);
    if (!empty($this->fetchedObject) && !empty($this->fetchedObject->location)) {
      $result->addStatusMessage($this->fetchedObject->location);
    }
  }

}
