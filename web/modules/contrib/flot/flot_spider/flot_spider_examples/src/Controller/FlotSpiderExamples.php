<?php

namespace Drupal\flot_spider_examples\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * FlotSpiderExamples Class.
 */
class FlotSpiderExamples extends ControllerBase {

  /**
   * The controller content.
   */
  public function content() {
    $d1 = [[0, 10], [1, 20], [2, 80], [3, 70], [4, 60]];
    $d2 = [[0, 30], [1, 25], [2, 50], [3, 60], [4, 95]];
    $d3 = [[0, 50], [1, 40], [2, 60], [3, 95], [4, 30]];
    $data = [
      [
        'label' => $this->t("Pies"),
        'color' => "green",
        'data' => $d1,
        'spider' => [
          'show' => TRUE,
          'lineWidth' => 12,
        ],
      ],
      [
        'label' => $this->t("Apples"),
        'color' => "orange",
        'data' => $d2,
        'spider' => ['show' => TRUE],
      ],
      [
        'label' => $this->t("Cherries"),
        'color' => "red",
        'data' => $d3,
        'spider' => ['show' => TRUE],
      ],
    ];
    $options = [
      'series' => [
        'editMode' => 'v',
        'editable' => TRUE,
        'spider' => [
          'active' => TRUE,
          'highlight' => ['mode' => "area"],
          'legs' => [
            'data' => [
              ['label' => "OEE"],
              ['label' => "MOE"],
              ['label' => "OER"],
              ['label' => "OEC"],
              ['label' => $this->t("Quality")],
            ],
            'legScaleMax' => 1,
            'legScaleMin' => 0.8,
          ],
          'spiderSize' => 0.9,
        ],
      ],
      'grid' => [
        'hoverable' => TRUE,
        'clickable' => TRUE,
        'editable' => TRUE,
        'tickColor' => "rgba(0,0,0,0.2)",
        'mode' => "radar",
      ],
    ];

    $output[] = [
      '#type' => 'flot',
      '#data' => $data,
      '#options' => $options,
      '#attached' => ['library' => ['flot_spider/flot_spider']],
    ];

    return $output;
  }

}
