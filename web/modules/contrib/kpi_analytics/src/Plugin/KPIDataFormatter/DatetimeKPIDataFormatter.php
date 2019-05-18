<?php

/**
 * @file
 * Contains \Drupal\kpi_analytics\Plugin\KPIVisualization\DateTimeKPIDataFormatter.php.
 */

namespace Drupal\kpi_analytics\Plugin\KPIDataFormatter;

use Drupal\Component\Utility\SortArray;
use Drupal\kpi_analytics\Plugin\KPIDataFormatterBase;

/**
 * Provides a 'DateTimeKPIDataFormatter' KPI data formatter.
 *
 * @KPIDataFormatter(
 *  id = "datetime_kpi_data_formatter",
 *  label = @Translation("Datetime KPI data formatter"),
 * )
 */
class DateTimeKPIDataFormatter extends KPIDataFormatterBase {

  /**
   * @inheritdoc
   */
  public function format(array $data) {
    $formatted_data = [];
    $date_formatter = \Drupal::service('date.formatter');

    usort($data, $this->sortByField('created'));

    // Combine multiple values in one value.
    foreach ($data as $value) {
      if (isset($value['created'])) {
        $value['created'] = $date_formatter->format($value['created'], '', 'Y-m');
      }
      $formatted_data[] = $value;
    }
    return $formatted_data;
  }

  function sortByField($field) {
    return function ($a, $b) use ($field) {
      if ($a[$field] == $b[$field]) {
        return 0;
      }
      else {
        return ($a[$field] < $b[$field]) ? -1 : 1;
      }
    };
  }
}