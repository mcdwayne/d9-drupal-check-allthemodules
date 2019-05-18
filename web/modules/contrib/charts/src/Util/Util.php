<?php

namespace Drupal\charts\Util;

use Drupal\views\ViewExecutable;

/**
 * Util.
 */
class Util {

  /**
   * Views Data.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   View.
   * @param array $labelValues
   *   Label Values.
   * @param string $labelField
   *   Label Field.
   * @param array $color
   *   Colors.
   * @param string|null $attachmentChartTypeOption
   *   Attachment Chart Type Option.
   *
   * @return array
   *   Data.
   */
  public static function viewsData(ViewExecutable $view = NULL, array $labelValues = [], $labelField = '', array $color = [], $attachmentChartTypeOption = NULL) {
    $data = [];
    foreach ($view->result as $row_number => $row) {
      $numberFields = 0;
      $rowData = [];
      foreach ($labelValues as $fieldId => $rowDataValue) {
        $alter_text = $view->field[$labelField]->options['alter']['alter_text'];
        if ($alter_text) {
          $text = $view->field[$labelField]->options['alter']['text'];
          $tokenized_text = trim(str_replace("\n", '', strip_tags($view->field[$labelField]->tokenizeValue($text, $row_number))));
        }
        $rowData[$numberFields] = [
          'value' => $view->field[$fieldId]->getValue($row),
          'label_field' => ($alter_text) ? $tokenized_text : $view->field[$labelField]->getValue($row),
          'label' => $view->field[$fieldId]->label(),
          'color' => $color[$fieldId],
          'type' => $attachmentChartTypeOption,
        ];
        $numberFields++;
      }
      $data[$row_number] = $rowData;
    }

    return $data;
  }

  /**
   * Removes unselected fields.
   *
   * @param array $valueField
   *   Value Field.
   *
   * @return array
   *   Field Values.
   */
  public static function removeUnselectedFields(array $valueField = []) {
    $fieldValues = [];
    foreach ($valueField as $key => $value) {
      if (!empty($value)) {
        $fieldValues[$key] = $value;
      }
    }
    return $fieldValues;
  }

  /**
   * Creates chart data to be used later by visualization frameworks.
   *
   * @param array $data
   *   Data.
   *
   * @return array
   *   Chart Data.
   */
  public static function createChartableData(array $data = []) {
    $chartData = [];
    $categories = [];
    $seriesData = [];

    for ($i = 0; $i < count($data[0]); $i++) {

      $seriesRowData = [
        'name' => '',
        'color' => '',
        'type' => '',
        'data' => [],
      ];
      for ($j = 0; $j < count($data); $j++) {
        $categories[$j] = $data[$j][$i]['label_field'];
        $seriesRowData['name'] = $data[$j][$i]['label'];
        $seriesRowData['type'] = $data[$j][$i]['type'];
        $seriesRowData['color'] = $data[$j][$i]['color'];
        array_push($seriesRowData['data'], (json_decode(($data[$j][$i]['value']))));
      }
      array_push($seriesData, $seriesRowData);
    }
    $chartData[0] = $categories;
    $chartData[1] = $seriesData;

    return $chartData;
  }

  /**
   * Checks for missing libraries necessary for data visualization.
   *
   * @param string $libraryPath
   *   Library Path.
   */
  public static function checkMissingLibrary($libraryPath = '') {
    if (!file_exists(DRUPAL_ROOT . DIRECTORY_SEPARATOR . $libraryPath)) {
      drupal_set_message(t('Charting libraries might not be installed at the location @libraryPath.', [
        '@libraryPath' => $libraryPath,
      ]), 'error');
    }
  }

}
