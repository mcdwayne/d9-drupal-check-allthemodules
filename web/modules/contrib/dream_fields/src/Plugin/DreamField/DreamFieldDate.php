<?php

namespace Drupal\dream_fields\Plugin\DreamField;

use Drupal\dream_fields\DreamFieldPluginBase;
use Drupal\dream_fields\FieldBuilderInterface;

/**
 * Plugin implementation of 'date'.
 *
 * @DreamField(
 *   id = "date",
 *   label = @Translation("Date"),
 *   description = @Translation("This will add an input field for a date and will be outputted with the label in the front."),
 *   weight = -4,
 *   preview = "images/date-dreamfields.png",
 *   preview_provider = "dream_fields",
 *   provider = "datetime",
 *   field_types = {
 *     "datetime"
 *   },
 * )
 */
class DreamFieldDate extends DreamFieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getForm() {
    $form = [];
    $form['time_as_well'] = [
      '#title' => t('Do you want fields for time as well?'),
      '#type' => 'checkbox',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function saveForm($values, FieldBuilderInterface $field_builder) {
    $field_builder
      ->setField('datetime', [
        'datetime_type' => $values['time_as_well'] ? 'datetime' : 'date',
      ])
      ->setDisplay('datetime_default')
      ->setWidget('datetime_default');
  }

}
