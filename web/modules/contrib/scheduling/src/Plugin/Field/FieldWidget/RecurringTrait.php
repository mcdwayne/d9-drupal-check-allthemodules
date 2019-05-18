<?php

namespace Drupal\scheduling\Plugin\Field\FieldWidget;

trait RecurringTrait {

  protected function buildRecurringWidget($item, $id) {

    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'recurring',
          'row',
        ],
      ],
      'mode' => [
        '#type' => 'value',
        '#value' => $item['mode'],
      ],
      'weekdays' => [
        '#type' => 'checkboxes',
        '#multiple' => TRUE,
        '#options' => [
          'Su' => $this->t('Su'),
          'Mo' => $this->t('Mo'),
          'Tu' => $this->t('Tu'),
          'We' => $this->t('We'),
          'Th' => $this->t('Th'),
          'Fr' => $this->t('Fr'),
          'Sa' => $this->t('Sa'),
        ],
        '#default_value' => isset($item['weekdays']) ? $item['weekdays'] : [],
      ],
      'from' => [
        '#type' => 'datetime',
        '#date_date_element' => 'none',
        '#date_time_element' => 'time',
        '#default_value' => $item['from'],
      ],
      'to' => [
        '#type' => 'datetime',
        '#date_date_element' => 'none',
        '#date_time_element' => 'time',
        '#default_value' => $item['to'],
      ],
//      '#states' => [
//        'visible' => [
//          ':input[name="' . $name . '"]' => ['value' => 'recurring'],
//        ],
//      ],
    ];

  }

}
