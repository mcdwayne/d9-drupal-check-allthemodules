<?php
/**
 * @file
 * Contains \Drupal\monitoring\Plugin\monitoring\SensorPlugin\WatchdogAggregatorSensorPlugin.
 */

namespace Drupal\monitoring\Plugin\monitoring\SensorPlugin;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Url;
use Drupal\monitoring\SensorPlugin\ExtendedInfoSensorPluginInterface;

/**
 * Watchdog aggregator which handles replacement of variables in the message.
 *
 * @SensorPlugin(
 *   id = "watchdog_aggregator",
 *   label = @Translation("Watchdog Aggregator"),
 *   description = @Translation("Aggregator able to query the watchdog table."),
 *   addable = TRUE
 * )
 */
class WatchdogAggregatorSensorPlugin extends DatabaseAggregatorSensorPlugin implements ExtendedInfoSensorPluginInterface {

  /**
   * {@inheritdoc}
   */
  protected $configurableTable = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $configurableTimestampField = FALSE;

  /**
   * {@inheritdoc}
   */
  public function verboseResultUnaggregated(array &$output) {
    parent::verboseResultUnaggregated($output);
    // If sensor has message and variables, remove variables header.
    if (isset($output['verbose_sensor_result']['#rows']) && array_key_exists('message', $output['verbose_sensor_result']['#header']) && array_key_exists('variables', $output['verbose_sensor_result']['#header'])) {
      unset($output['verbose_sensor_result']['#header']['variables']);
      // Replace the message for every row.
      foreach ($output['verbose_sensor_result']['#rows'] as $delta => $row) {
        // Link the watchdog id to the dblog.
        $output['verbose_sensor_result']['#rows'][$delta]['wid'] = \Drupal::l($row['wid'], Url::fromUserInput('/admin/reports/dblog/event/' . $row['wid']));

        // Replace variables in message.
        $output['verbose_sensor_result']['#rows'][$delta]['message'] = new FormattableMarkup($row['message'], unserialize($row['variables']));
        // Do not render the raw message & variables in the row.
        unset($output['verbose_sensor_result']['#rows'][$delta]['variables']);
      };
    }
  }

  /**
   * {@inheritdoc}
   */
  public function verboseResultHistory(array &$output) {
    parent::verboseResultHistory($output);
    // Add cutoff info message.
    if (isset($output['verbose_sensor_history']['#description'])) {
      $output['verbose_sensor_history']['#description'] = t('Records in dblog limited to :limit records. :parent_info', [
        ':limit' => \Drupal::configFactory()->get('dblog.settings')->get('row_limit'),
        ':parent_info' => $output['verbose_sensor_history']['#description'],
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultConfiguration() {
    $default_config = array(
      'settings' => array(
        'table' => 'watchdog',
        'time_interval_field' => 'timestamp',
      ),
    );
    return $default_config;
  }

}
