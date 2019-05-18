<?php
/**
 * @file
 * Contains \Drupal\monitoring\Plugin\monitoring\SensorPlugin\ImageMissingStyleSensorPlugin.
 */

namespace Drupal\monitoring\Plugin\monitoring\SensorPlugin;

use Drupal\Core\Link;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\monitoring\Result\SensorResultInterface;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;

/**
 * Monitors image derivate creation errors from dblog.
 *
 * @SensorPlugin(
 *   id = "image_style_missing",
 *   label = @Translation("Image Missing Style"),
 *   description = @Translation("Monitors image derivate creation errors from database log."),
 *   provider = "image",
 *   addable = FALSE
 * ),
 *
 * Displays image derivate with highest occurrence as message.
 */
class ImageMissingStyleSensorPlugin extends WatchdogAggregatorSensorPlugin {

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
    // Extends the watchdog query.
    $query = parent::getAggregateQuery();
    $query->addField('watchdog', 'variables');
    $query->groupBy('variables');
    $query->orderBy('records_count', 'DESC');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function runSensor(SensorResultInterface $result) {
    parent::runSensor($result);
    if (!empty($this->fetchedObject)) {
      $variables = unserialize($this->fetchedObject->variables);
      if (isset($variables['%source_image_path'])) {
        $result->addStatusMessage($variables['%source_image_path']);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function resultVerbose(SensorResultInterface $result) {
    // The unaggregated result in a fieldset.
    $output = parent::resultVerbose($result);

    // The result aggregated per user.
    $this->verboseResultCounting($output);

    return $output;
  }

  /**
   * Get the aggregated table verbose output.
   *
   * @param array $output
   *   The output array, at which we will add the aggregated table
   *   verbose output.
   *
   * @return array
   *   Aggregated result table.
   */
  public function verboseResultCounting(array &$output) {
    if ($this->sensorConfig->getSetting('verbose_fields')) {
      // Fetch the top 20 matching entries, aggregated.
      $query = $this->getAggregateQuery();
      // Also get the latest occurrence (highest timestamp).
      $query->addExpression('MAX(timestamp)', 'timestamp');
      $query_result = $query->range(0, 20)->execute();
      $this->queryString = $query_result->getQueryString();

      $rows = $this->buildTableRows($query_result->fetchAll());
      $results = [];
      foreach ($rows as $key => $row) {
        $results[$key] = [];
        $variables = unserialize($row['variables']);
        $results[$key]['file'] = $variables['%source_image_path'];
        $results[$key]['count'] = $row['records_count'];
        $file = \Drupal::entityQuery('file')
          ->condition('uri', $variables['%source_image_path'])
          ->execute();
        if (!empty($file)) {
          $file = file_load(array_shift($file));
          /** @var \Drupal\file\FileUsage\FileUsageInterface $usage */
          $list_usages = \Drupal::service('file.usage')->listUsage($file);
          $usages = 0;
          foreach (new RecursiveIteratorIterator(new RecursiveArrayIterator($list_usages)) as $sub) {
            $usages += (int) $sub;
          }
          $results[$key]['usages'] = Link::fromTextAndUrl(
            \Drupal::translation()
              ->formatPlural($usages, '1 place', '@count places'),
            Url::fromUserInput('/admin/content/files/usage/' . $file->id()));
        }
        else {
          $results[$key]['usages'] = ['#markup' => ''];
        }
        $results[$key]['timestamp'] = \Drupal::service('date.formatter')->format($row['timestamp'], 'short');
      }

      $output['aggregated_result'] = array(
        '#type' => 'verbose_table_result',
        '#title' => t('Aggregated result'),
        '#header' => $this->buildTableHeader($results),
        '#rows' => $results,
        '#query' => $query_result->getQueryString(),
        '#query_args' => $query->getArguments(),
      );
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function verboseResultUnaggregated(array &$output) {
    parent::verboseResultUnaggregated($output);
    foreach ($output['verbose_sensor_result']['#rows'] as $key => $row) {
      /** @var \Drupal\Component\Render\FormattableMarkup $message */
      $message = $row['message'];
      $tmp_str = substr($message->jsonSerialize(), strpos($message->jsonSerialize(), '>') + 1);
      $output['verbose_sensor_result']['#rows'][$key]['path'] = substr($tmp_str, 0, strpos($tmp_str, '<'));
      unset($output['verbose_sensor_result']['#rows'][$key]['message']);
      unset($output['verbose_sensor_result']['#rows'][$key]['timestamp']);
      $output['verbose_sensor_result']['#rows'][$key]['timestamp'] = $row['timestamp'];
    }
    $output['verbose_sensor_result']['#header']['path'] = 'image path';
    unset($output['verbose_sensor_result']['#header']['message']);
    unset($output['verbose_sensor_result']['#header']['timestamp']);
    $output['verbose_sensor_result']['#header']['timestamp'] = 'timestamp';
  }

}
