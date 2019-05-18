<?php

namespace Drupal\flot_examples\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Display a chart demonstrating hiding series.
 */
class SeriesToggle extends ControllerBase {

  /**
   * Function seriesToggle.
   */
  public function content() {
    $data = $this::getData();
    $data['usa']['label'] = $this->t('USA');
    $data['russia']['label'] = $this->t('Russia');
    $data['uk']['label'] = $this->t('UK');
    $data['germany']['label'] = $this->t('Germany');
    $data['denmark']['label'] = $this->t('Demmark');
    $data['sweden']['label'] = $this->t('Sweden');
    $data['norway']['label'] = $this->t('Norway');
    $options = [
      'yaxis' => ['min' => 0],
      'xaxis' => ['tickDecimals' => 0],
    ];
    $text = [];
    $array = [':one' => 'http://www.sipri.org/'];
    $text[] = $this->t('This example shows military budgets for various countries in constant (2005) million US dollars (source: <a href=":one">SIPRI</a>).', $array);
    $text[] = $this->t("Since all data is available client-side, it's pretty easy to make the plot interactive. Try turning countries on and off with the checkboxes next to the plot.");
    $output['flot'] = [
      '#type' => 'flot',
      '#theme' => 'flot_examples_series_toggle',
      '#options' => $options,
      '#data' => $data,
      '#text' => $text,
    ];

    return $output;
  }

  /**
   * Fetch the data from the raw text file.
   */
  private function getData() {
    $file_path = DRUPAL_ROOT . '/' . drupal_get_path('module', 'flot_examples') . '/src/Controller/MilitaryData.txt';
    $file = fopen($file_path, "r") or die("Unable to open file: $file_path");
    $countries = [
      'usa', 'russia', 'uk', 'germany',
      'denmark', 'sweden', 'norway',
    ];
    $data = [];
    while (!feof($file)) {
      $line = fgets($file);
      $values = explode(', ', $line);
      if (count($values) > 1) {
        $year = $values[0];
        foreach ($countries as $key => $country) {
          if ($values[$key + 1] != "") {
            $data[$country]['data'][] = [$year, $values[$key + 1]];
          }
        }
      }
    }
    fclose($file);
    return $data;
  }

}
