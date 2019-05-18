<?php

namespace Drupal\flot_examples\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Display a chart that demonstrates region selection and zooming.
 */
class Selection extends ControllerBase {

  /**
   * Selection.
   */
  public function content() {
    $data = $this::readData();
    $options = [
      'series' => [
        'lines' => ['show' => TRUE],
        'points' => ['show' => TRUE],
      ],
      'legend' => ['noColumns' => 2],
      'xaxis' => ['tickDecimals' => 0],
      'yaxis' => ['min' => 0],
      'selection' => ['mode' => 'x'],
    ];
    $text = [];
    $array = [':one' => 'http://en.wikipedia.org/wiki/List_of_countries_by_carbon_dioxide_emissions_per_capita'];
    $text[] = $this->t('1000 kg. CO<sub>2</sub> emissions per year per capita for various countries (source: <a href=":one">Wikipedia</a>).', $array);
    $text[] = $this->t('Flot supports selections through the selection plugin. You can enable rectangular selection or one-dimensional selection if the user should only be able to select on one axis. Try left-click and drag on the plot above where selection on the x axis is enabled.');
    $text[] = $this->t('You selected: <span id="selection"></span>');
    $text[] = $this->t('The plot command returns a plot object you can use to control the selection. Click the buttons below.');
    $text[] = [
      [
        '#type' => 'button',
        '#value' => $this->t('Clear selection'),
        '#attributes' => ['id' => ['clearSelection']],
      ],
      [
        '#type' => 'button',
        '#value' => $this->t('Select year 1994'),
        '#attributes' => ['id' => ['setSelection']],
      ],
    ];
    $text[] = $this->t('Selections are really useful for zooming. Just replot the chart with min and max values for the axes set to the values in the "plotselected" event triggered. Enable the checkbox below and select a region again.');
    $text[] = [
      [
        '#type' => 'checkbox',
        '#attributes' => ['id' => ['zoom']],
        '#title' => $this->t('Zoom to selection'),
      ],
    ];

    $output['flot'] = [
      '#type' => 'flot',
      '#theme' => 'flot_examples',
      '#data' => $data,
      '#options' => $options,
      '#attached' => ['library' => ['flot_examples/selection']],
      '#text' => $text,
    ];

    return $output;
  }

  /**
   * Fetch the raw data from the data file.
   */
  private function readData() {
    $filename = "CO2CountryData.txt";
    $file_path = DRUPAL_ROOT . '/' . drupal_get_path('module', 'flot_examples') . '/src/Controller/' . $filename;
    $file = fopen($file_path, "r") or die("Unable to open file: $file_path");
    $countries = [
      $this->t("United States"), $this->t("Russia"), $this->t("United Kingdom"),
      $this->t("Germany"), $this->t("Denmark"), $this->t("Sweden"), $this->t("Norway"),
    ];
    $data = [];
    foreach ($countries as $key => $country) {
      $data[$key]['label'] = $country;
    }
    while (!feof($file)) {
      $line = fgets($file);
      $values = explode(', ', $line);
      if (count($values) > 1) {
        foreach ($countries as $key => $country) {
          if ($values[$key + 1] != "") {
            $data[$key]['data'][] = [$values[0], $values[$key + 1]];
          }
        }
      }
    }
    fclose($file);
    return $data;
  }

}
