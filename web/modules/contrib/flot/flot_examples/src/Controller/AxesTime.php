<?php

namespace Drupal\flot_examples\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Display a chart to demontrate date/time handling on the axes.
 */
class AxesTime extends ControllerBase {

  /**
   * Time Axes.
   */
  public function content() {
    $d[] = $this::getData('CO2.txt');
    $options = [
      "xaxis" => ["mode" => "time"],
    ];
    $text = [];
    $text[] = $this->t('Monthly mean atmospheric CO<sub>2</sub> in PPM at Mauna Loa, Hawaii (source: <a href=":one">NOAA/ESRL</a>).', [':one' => 'http://www.esrl.noaa.gov/gmd/ccgg/trends/']);

    $text[] = $this->t('If you tell Flot that an axis represents time, the data will be interpreted as timestamps and the ticks adjusted and formatted accordingly.');

    $text[] = [
      [
        '#type' => 'button',
        '#value' => $this->t('Whole Period'),
        '#attributes' => ['id' => ['whole']],
        '#label' => $this->t('Zoom to:'),
      ],
      [
        '#type' => 'button',
        '#value' => $this->t('1990-2000'),
        '#attributes' => ['id' => ['nineties']],
      ],
      [
        '#type' => 'button',
        '#value' => $this->t('1996-2000'),
        '#attributes' => ['id' => ['latenineties']],
      ],
    ];

    $text[] = [
      [
        '#type' => 'button',
        '#value' => $this->t('1999 by quarter'),
        '#attributes' => ['id' => ['ninetyninequarters']],
        '#label' => $this->t('Zoom to:'),
      ],
      [
        '#type' => 'button',
        '#value' => $this->t('1999 by month'),
        '#attributes' => ['id' => ['ninetynine']],
      ],
      [
        '#type' => 'button',
        '#value' => $this->t('Last week of 1999'),
        '#attributes' => ['id' => ['lastweekninetynine']],
      ],
      [
        '#type' => 'button',
        '#value' => $this->t('Dec. 31, 1999'),
        '#attributes' => ['id' => ['lastdayninetynine']],
      ],
    ];

    $text[] = $this->t('The timestamps must be specified as Javascript timestamps, as milliseconds since January 1, 1970 00:00. This is like Unix timestamps, but in milliseconds instead of seconds (remember to multiply with 1000!).');

    $text[] = $this->t('As an extra caveat, the timestamps are interpreted according to UTC and, by default, displayed as such. You can set the axis "timezone" option to "browser" to display the timestamps in the user\'s timezone, or, if you use timezoneJS, you can specify a time zone.');
    $output['flot'] = [
      '#type' => 'flot',
      '#data' => $d,
      '#options' => $options,
      '#theme' => 'flot_examples',
      '#text' => $text,
      '#attached' => ['library' => ['flot_examples/axes_time']],
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
