<?php

namespace Drupal\scheduling\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;

trait RangeTrait {

  protected function buildRangeWidget($item, $id) {

    $name = Html::cleanCssIdentifier(str_replace('value', 'mode', $id) . '_select');

    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'range',
          'row',
        ],
      ],
      'mode' => [
        '#type' => 'value',
        '#value' => $item['mode'],
      ],
      'from' => [
        '#type' => 'datetime',
        '#default_value' => $item['from'],
      ],
      'to' => [
        '#type' => 'datetime',
        '#default_value' => $item['to'],
      ],
//      '#states' => [
//        'visible' => [
//          ':input[name="' . $name . '"]' => ['value' => 'range'],
//        ],
//      ],
    ];

  }

}
