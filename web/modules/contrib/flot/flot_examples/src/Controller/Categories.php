<?php

namespace Drupal\flot_examples\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Display a barchart.
 */
class Categories extends ControllerBase {

  /**
   * Function categories.
   */
  public function content() {
    $options = [
      "series" => [
        "bars" => [
          "show" => TRUE,
          "barWidth" => .6,
          "align" => "center",
        ],
      ],
      "xaxis" => [
        "mode" => "categories",
        "ticklength" => 0,
      ],
    ];
    $data[] = [
      [$this->t('January'), 10], [$this->t('February'), 8], [$this->t('March'), 4],
      [$this->t('April'), 13], [$this->t('May'), 17], [$this->t('June'), 9],
    ];
    $text = $this->t('With the categories plugin you can plot categories/textual data easily.');
    $output['flot'] = [
      '#type' => 'flot',
      '#data' => $data,
      '#options' => $options,
      '#theme' => 'flot_examples',
      '#text' => [$text],
    ];
    return $output;
  }

}
