<?php

namespace Drupal\flot_examples\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Display a chart in canvas mode.
 */
class Canvas extends ControllerBase {

  /**
   * Zooming.
   */
  public function content() {
    $oilprices = $this::getData('OilData.txt');
    $exchangerates = $this::getData('ExchangeData.txt');
    $data[] = [
      'data' => $oilprices,
      'label' => "Oil price ($)",
    ];
    $data[] = [
      'data' => $exchangerates,
      'label' => "USD/EUR exchange rate",
      'yaxis' => 2,
    ];
    $options = [
      'canvas' => TRUE,
      'xaxes' => [['mode' => "time"]],
      'yaxes' => [['min' => 0], [
        'position' => "right",
        'alignTicksWithAxis' => 1,
        'tickFormatter' => 'function',
      ],
      ],
      'legend' => ['position' => "sw"],
    ];
    $text = [];
    $text[] = $this->t('This example uses the same dataset (raw oil price in US $/barrel of crude oil vs. the exchange rate from US $ to €) as the multiple-axes example, but uses the canvas plugin to render axis tick labels using canvas text.');
    $text[] = [[
      '#type' => 'checkbox',
      '#title' => $this->t('Enable canvas text'),
    ],
    ];

    $output[] = [
      '#type' => 'flot',
      '#theme' => 'flot_examples',
      '#data' => $data,
      '#options' => $options,
      '#text' => $text,
      '#attached' => ['library' => ['flot_examples/canvas']],
    ];

    return $output;
  }

  /**
   * Fetch the raw data from the data file.
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
