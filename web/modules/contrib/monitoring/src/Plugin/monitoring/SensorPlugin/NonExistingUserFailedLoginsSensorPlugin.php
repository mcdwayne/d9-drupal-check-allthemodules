<?php
/**
 * @file
 * Contains \Drupal\monitoring\Plugin\monitoring\SensorPlugin\NonExistingUserFailedLoginsSensorPlugin.
 */

namespace Drupal\monitoring\Plugin\monitoring\SensorPlugin;

use Drupal\monitoring\Result\SensorResultInterface;

/**
 * Monitors non existing user failed login from dblog messages.
 *
 * @SensorPlugin(
 *   id = "user_void_failed_logins",
 *   label = @Translation("Non Existing User Failed Logins"),
 *   description = @Translation("Monitors non existing user failed login from dblog messages."),
 *   addable = FALSE
 * )
 *
 * Helps to identify bots or brute force attacks.
 */
class NonExistingUserFailedLoginsSensorPlugin extends WatchdogAggregatorSensorPlugin {

  /**
   * {@inheritdoc}
   */
  public function getAggregateQuery() {
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
    $records_count = 0;
    foreach ($this->getAggregateQuery()->execute() as $row) {
      $records_count += $row->records_count;
      $variables = unserialize($row->variables);
      $result->addStatusMessage('@ip: @count', array('@ip' => $variables['%ip'], '@count' => $row->records_count));
    }

    $result->setValue($records_count);
  }

  /**
   * {@inheritdoc}
   */
  public function resultVerbose(SensorResultInterface $result) {
    // The unaggregated result in a fieldset.
    $output = parent::resultVerbose($result);

    // The result aggregated per ip.
    $this->verboseResultCounting($output);

    return $output;
  }

  /**
   * Get the verbose results of the attempts per ip.
   *
   * @param array $output
   *   The output array, at which we will add the attempts per user result.
   */
  public function verboseResultCounting(array &$output) {
    if ($this->sensorConfig->getSetting('verbose_fields')) {
      // Fetch the last 20 matching entries, aggregated.
      $query = $this->getAggregateQuery();
      $query_result = $query->range(0, 20)->execute();
      $this->queryString = $query_result->getQueryString();

      $rows = $this->buildTableRows($query_result->fetchAll());
      $results = [];
      foreach ($rows as $key => $row) {
        $results[$key] = [];
        $variables = unserialize($row['variables']);
        $results[$key]['ip'] = $variables['%ip'];
        $results[$key]['attempts'] = $row['records_count'];
      }
      $output['attempts_per_ip'] = array(
        '#type' => 'verbose_table_result',
        '#title' => t('Attempts per ip'),
        '#rows' => $results,
        '#header' => $this->buildTableHeader($results),
        '#query' => $query_result->getQueryString(),
        '#query_args' => $query->getArguments(),
      );
    }
  }

}
