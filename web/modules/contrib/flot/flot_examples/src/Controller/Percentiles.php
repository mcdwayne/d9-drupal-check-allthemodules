<?php

namespace Drupal\flot_examples\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Displays a chart that demonstrates the fillbetween plugin.
 */
class Percentiles extends ControllerBase {

  /**
   * Zooming.
   */
  public function content() {

    $males = $this::readData('PercentileMaleData.txt');
    $females = $this::readData('PercentileFemaleData.txt');
    $dataset = [
      [
        'label' => "Female mean",
        'data' => $females["mean"],
        'lines' => ['show' => TRUE],
        'color' => "rgb(255,50,50)",
      ],
      [
        'id' => "f15%",
        'data' => $females["15%"],
        'lines' => [
          'show' => TRUE,
          'lineWidth' => 0,
          'fill' => FALSE,
        ],
        'color' => "rgb(255,50,50)",
      ],
      [
        'id' => "f25%",
        'data' => $females["25%"],
        'lines' => [
          'show' => TRUE,
          'lineWidth' => 0,
          'fill' => 0.2,
        ],
        'color' => "rgb(255,50,50)",
        'fillBetween' => "f15%",
      ],
      [
        'id' => "f50%",
        'data' => $females["50%"],
        'lines' => [
          'show' => TRUE,
          'lineWidth' => 0.5,
          'fill' => 0.4,
          'shadowSize' => 0,
        ],
        'color' => "rgb(255,50,50)",
        'fillBetween' => "f25%",
      ],
      [
        'id' => "f75%",
        'data' => $females["75%"],
        'lines' => [
          'show' => TRUE,
          'lineWidth' => 0,
          'fill' => 0.4,
        ],
        'color' => "rgb(255,50,50)",
        'fillBetween' => "f50%",
      ],
      [
        'id' => "f85%",
        'data' => $females["85%"],
        'lines' => [
          'show' => TRUE,
          'lineWidth' => 0,
          'fill' => 0.2,
        ],
        'color' => "rgb(255,50,50)",
        'fillBetween' => "f75%",
      ],
      [
        'label' => "Male mean",
        'data' => $males["mean"],
        'lines' => ['show' => TRUE],
        'color' => "rgb(50,50,255)",
      ],
      [
        'id' => "m15%",
        'data' => $males["15%"],
        'lines' => [
          'show' => TRUE,
          'lineWidth' => 0,
          'fill' => FALSE,
        ],
        'color' => "rgb(50,50,255)",
      ],
      [
        'id' => "m25%",
        'data' => $males["25%"],
        'lines' => [
          'show' => TRUE,
          'lineWidth' => 0,
          'fill' => 0.2,
        ],
        'color' => "rgb(50,50,255)",
        'fillBetween' => "m15%",
      ],
      [
        'id' => "m50%",
        'data' => $males["50%"],
        'lines' => [
          'show' => TRUE,
          'lineWidth' => 0.5,
          'fill' => 0.4,
          'shadowSize' => 0,
        ],
        'color' => "rgb(50,50,255)",
        'fillBetween' => "m25%",
      ],
      [
        'id' => "m75%",
        'data' => $males["75%"],
        'lines' => [
          'show' => TRUE,
          'lineWidth' => 0,
          'fill' => 0.4,
        ],
        'color' => "rgb(50,50,255)",
        'fillBetween' => "m50%",
      ],
      [
        'id' => "m85%",
        'data' => $males["85%"],
        'lines' => [
          'show' => TRUE,
          'lineWidth' => 0,
          'fill' => 0.2,
        ],
        'color' => "rgb(50,50,255)",
        'fillBetween' => "m75%",
      ],
    ];

    $options = [
      'xaxis' => ['tickDecimals' => 0],
      'legend' => ['position' => "se"],
    ];
    $text = [];
    $text[] = $this->t('Height in centimeters of individuals from the US (2003-2006) as function of age in years (source: <a href=":one">CDC</a>). The 15%-85%, 25%-75% and 50% percentiles are indicated.', [':one' => 'http://www.cdc.gov/nchs/data/nhsr/nhsr010.pdf']);
    $text[] = $this->t('For each point of a filled curve, you can specify an arbitrary bottom. As this example illustrates, this can be useful for plotting percentiles. If you have the data sets available without appropriate fill bottoms, you can use the fillbetween plugin to compute the data point bottoms automatically.');
    $output[] = [
      '#type' => 'flot',
      '#theme' => 'flot_examples',
      '#text' => $text,
      '#data' => $dataset,
      '#options' => $options,
      '#attached' => ['library' => ['flot_examples/percentiles']],
    ];

    return $output;
  }

  /**
   * Fetch the raw data from the data file.
   */
  private function readData($filename) {
    $file_path = DRUPAL_ROOT . '/' . drupal_get_path('module', 'flot_examples') . '/src/Controller/' . $filename;
    $file = fopen($file_path, "r") or die("Unable to open file: $file_path");
    $stats = ["10%", "15%", "25%", "50%", "75%", "85%", "90%", "mean"];
    $data = [];
    while (!feof($file)) {
      $line = fgets($file);
      $values = explode(', ', $line);
      if (count($values) > 1) {
        foreach ($stats as $key => $stat) {
          $data[$stat][] = [$values[0], $values[$key + 1]];
        }
      }
    }
    fclose($file);
    return $data;
  }

}
