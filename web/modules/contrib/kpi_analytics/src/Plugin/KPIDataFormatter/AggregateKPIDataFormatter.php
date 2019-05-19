<?php

/**
 * @file
 * Contains \Drupal\kpi_analytics\Plugin\KPIVisualization\AggregateKPIDataFormatter.php.
 */

namespace Drupal\kpi_analytics\Plugin\KPIDataFormatter;

use Drupal\kpi_analytics\Plugin\KPIDataFormatterBase;

/**
 * Provides a 'AggregateKPIDataFormatter' KPI data formatter.
 *
 * @KPIDataFormatter(
 *  id = "aggregate_kpi_data_formatter",
 *  label = @Translation("Aggregate KPI data formatter"),
 * )
 */
class AggregateKPIDataFormatter extends KPIDataFormatterBase {

  /**
   * @inheritdoc
   */
  public function format(array $data) {
    $formatted_data = [];
    $date_formatter = \Drupal::service('date.formatter');


    // Combine multiple values in one value.
    foreach ($data as $value) {
      if (isset($value['created'])) {
        $value['created'] = $date_formatter->format($value['created'], '', 'Y-m');
      }
      $formatted_data[] = $value;
    }
    return $formatted_data;
  }
}