<?php

namespace Drupal\dream_fields\Plugin\DreamField;
use Drupal\dream_fields\DreamFieldPluginBase;
use Drupal\dream_fields\FieldBuilderInterface;

/**
 * Plugin implementation of 'number'.
 *
 * @DreamField(
 *   id = "number",
 *   label = @Translation("Number input"),
 *   description = @Translation("This will add an input field for a number and will be outputted with the label in the front."),
 *   weight = -7,
 *   preview = "images/number-dreamfields.png",
 *   field_types = {
 *     "float",
 *     "decimal",
 *     "integer",
 *   },
 * )
 */
class DreamFieldNumber extends DreamFieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getForm() {
    $form = [];
    $form['number_type'] = [
      '#type' => 'radios',
      '#title' => t('Select which number format you want to use'),
      '#required' => TRUE,
      '#options' => [
        'integer' => 'Integer numbers like 1, 2, 3',
        'decimal' => 'Decimal numbers like 2.34, 5.15',
        'float' => 'Floating numbers like 3.141629',
      ],
      '#default_value' => 'integer',
    ];

    $form['thousand_separator'] = [
      '#type' => 'select',
      '#title' => t('Thousand marker'),
      '#options' => [
        ''  => t('- None -'),
        '.' => t('Decimal point'),
        ',' => t('Comma'),
        ' ' => t('Space'),
      ],
      '#default_value' => '',
      '#weight' => 0,
      '#states' => [
        'visible' => [
          ['[name="new_field_info[number][number_type]"]' => ['value' => 'decimal']],
          ['[name="new_field_info[number][number_type]"]' => ['value' => 'float']],
        ],
        'required' => [
          ['[name="new_field_info[number][number_type]"]' => ['value' => 'decimal']],
          ['[name="new_field_info[number][number_type]"]' => ['value' => 'float']],
        ],
      ],
    ];

    $form['decimal_separator'] = [
      '#type' => 'select',
      '#title' => t('Decimal marker'),
      '#options' => [
        '.' => t('Decimal point'),
        ',' => t('Comma'),
      ],
      '#default_value' => '.',
      '#weight' => 5,
      '#states' => [
        'visible' => [
          ['[name="new_field_info[number][number_type]"]' => ['value' => 'decimal']],
          ['[name="new_field_info[number][number_type]"]' => ['value' => 'float']],
        ],
        'required' => [
          ['[name="new_field_info[number][number_type]"]' => ['value' => 'decimal']],
          ['[name="new_field_info[number][number_type]"]' => ['value' => 'float']],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function saveForm($values, FieldBuilderInterface $field_builder) {

    $field_builder
      ->setField($values['number_type'])
      ->setWidget('number');

    if ($values['number_type'] === 'integer') {
      $field_builder->setDisplay('number_integer');
    }

    if ($values['number_type'] === 'decimal' || $values['number_type'] === 'float') {
      $field_builder->setDisplay('number_decimal', [
        'thousand_separator' => $values['thousand_separator'],
        'decimal_separator' => $values['decimal_separator'],
      ]);
    }
  }

}
