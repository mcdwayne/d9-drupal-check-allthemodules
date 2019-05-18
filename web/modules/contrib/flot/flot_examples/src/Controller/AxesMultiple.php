<?php

namespace Drupal\flot_examples\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Displays a graph to demonstrate multiple axes.
 */
class AxesMultiple extends ControllerBase {

  /**
   * Multiple Axes.
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
      'xaxes' => [['mode' => "time"]],
      'yaxes' => [
        ['min' => 0],
      ],
      'legend' => ['position' => "sw"],
    ];
    $text = [];
    $text[] = $this->t('Multiple axis support showing the raw oil price in US $/barrel of crude oil vs. the exchange rate from US $ to €.');
    $text[] = $this->t('As illustrated, you can put in multiple axes if you need to. For each data series, simply specify the axis number. In the options, you can then configure where you want the extra axes to appear.');
    $text[] = $this->t('Position axis <button>left</button> or <button>right</button>.');
    $output[] = [
      '#type' => 'flot',
      '#theme' => 'flot_examples',
      '#data' => $data,
      '#options' => $options,
      '#text' => $text,
      '#attached' => ['library' => ['flot_examples/axes_multiple']],
    ];
    return $output;
  }

  /**
   * Fetch the raw data from the data file.
   */
  private function getData($filename) {
    $data = [];
    $file_path = DRUPAL_ROOT . '/' . drupal_get_path('module', 'flot_examples') . '/src/Controller/' . $filename;
    $file = fopen($file_path, "r") or die("Unable to open file: $file_path");
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
