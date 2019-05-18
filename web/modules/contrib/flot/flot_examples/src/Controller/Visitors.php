<?php

namespace Drupal\flot_examples\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Demonstrate using two plots to control zooming in and out.
 */
class Visitors extends ControllerBase {

  /**
   * Visitors.
   */
  public function content() {
    $d = $this::getData('VisitorData.txt');
    // First correct the timestamps - they are recorded as the daily
    // midnights in UTC+0100, but Flot always displays dates in UTC
    // so we have to add one hour to hit the midnights in the plot.
    for ($i = 0; $i < count($d); $i++) {
      $d[$i][0] += 60 * 60 * 1000;
    }
    $options_p = [
      'xaxis' => [
        'mode' => "time",
        'tickLength' => 5,
      ],
      'selection' => ['mode' => "x"],
    ];
    $options_o = [
      'series' => [
        'lines' => [
          'show' => TRUE,
          'lineWidth' => 1,
        ],
        'shadowSize' => 0,
      ],
      'xaxis' => [
        'ticks' => [],
        'mode' => "time",
      ],
      'yaxis' => [
        'ticks' => [],
        'min' => 0,
        'autoscaleMargin' => 0.1,
      ],
      'selection' => ['mode' => "x"],
    ];
    $text = [];
    $text[] = $this->t('This plot shows visitors per day to the Flot homepage, with weekends colored.');
    $text[] = $this->t('The smaller plot is linked to the main plot, so it acts as an overview. Try dragging a selection on either plot, and watch the behavior of the other.');
    $output[] = [
      '#type' => 'flot_overview',
      '#theme' => 'flot_examples_visitors',
      '#text' => $text,
      '#options' => $options_p,
      '#options2' => $options_o,
      '#data' => [$d],
    ];
    return $output;
  }

  /**
   * Fetch the data from the raw text file.
   */
  private function getData($filename) {
    $file_path = DRUPAL_ROOT . '/' . drupal_get_path('module', 'flot_examples') . '/src/Controller/' . $filename;
    $file = fopen($file_path, "r") or die("Unable to open file: $file_path");
    $data = [];
    while (!feof($file)) {
      $line = fgets($file);
      $values = explode(', ', $line);
      if (count($values) == 2) {
        $data[] = [$values[0], $values[1]];
      }
    }
    fclose($file);
    return $data;
  }

}
