<?php
/**
 * @file
 * Contains \Drupal\monitoring\Plugin\monitoring\SensorPlugin\PhpNoticesSensorPlugin.
 */

namespace Drupal\monitoring\Plugin\monitoring\SensorPlugin;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\monitoring\Result\SensorResultInterface;

/**
 * Displays the most frequent PHP notices and errors.
 *
 * @SensorPlugin(
 *   id = "php_notices",
 *   provider = "dblog",
 *   label = @Translation("PHP notices (database log)"),
 *   description = @Translation("Displays the most frequent PHP notices and errors."),
 *   addable = FALSE
 * )
 */
class PhpNoticesSensorPlugin extends WatchdogAggregatorSensorPlugin {

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
  public function runSensor(SensorResultInterface $result) {
    parent::runSensor($result);
    if (!empty($this->fetchedObject->variables)) {
      $variables = unserialize($this->fetchedObject->variables);
      $variables['%file'] = $this->shortenFilename($variables['%file']);
      $result->setMessage('@count times: @error', ['@count' => (int) $this->fetchedObject->records_count, '@error' => new FormattableMarkup('%type: @message in %function (Line %line of %file).', $variables)]);
    };
  }

  /**
   * {@inheritdoc}
   */
  public function getAggregateQuery() {
    $query = parent::getAggregateQuery();
    $query->addField('watchdog', 'variables');
    $query->condition('type', 'php', NULL);
    // The message is the most recurring php error.
    $query->groupBy('variables');
    $query->orderBy('records_count', 'DESC');
    $query->range(0, 1);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuery() {
    $query = parent::getQuery();
    $query->addField('watchdog', 'variables');
    $this->addAggregateExpression($query);
    $query->condition('type', 'php', NULL);
    $query->groupBy('variables');

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
  public function verboseResultUnaggregated(array &$output) {
    parent::verboseResultUnaggregated($output);
    $rows = [];
    foreach ($output['verbose_sensor_result']['#rows'] as $delta => $row) {
      $variables = unserialize($row['variables']);
      $variables['%file'] = $this->shortenFilename($variables['%file']);
      $rows[$delta]['count'] = $row['records_count'];
      $rows[$delta]['type'] = $variables['%type'];
      $rows[$delta]['message'] = $variables['@message'];
      $rows[$delta]['caller'] = $variables['%function'];
      $rows[$delta]['file'] = $variables['%file'] . ':' . $variables['%line'];
    }
    $output['verbose_sensor_result']['#rows'] = $rows;
    $output['verbose_sensor_result']['#header'] = $this->buildTableHeader($rows);
  }

  /**
   * Removes the root path from a filename.
   *
   * @param string $filename
   *   Name of the file.
   *
   * @return string
   *   The shortened filename.
   */
  protected function shortenFilename($filename) {
    return str_replace(DRUPAL_ROOT . '/', '', $filename);
  }

}
