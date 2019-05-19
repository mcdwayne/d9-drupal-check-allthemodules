<?php

namespace Drupal\uc_product\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the Ubercart 'uc_weight' widget.
 *
 * @FieldWidget(
 *   id = "uc_weight",
 *   label = @Translation("Weight"),
 *   field_types = {
 *     "uc_weight",
 *   }
 * )
 */
class UcWeightWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $value = isset($items[$delta]->value) ? $items[$delta]->value : 0;
    $units = isset($items[$delta]->units) ? $items[$delta]->units : \Drupal::config('uc_store.settings')->get('weight.units');

    $element += [
      '#type' => 'fieldset',
      '#attributes' => [
        'class' => [
          'container-inline',
          'fieldgroup',
          'form-composite',
        ],
      ],
    ];

    $element['value'] = [
      '#type' => 'number',
      '#title' => $this->t('Weight'),
      '#title_display' => 'invisible',
      '#default_value' => $value,
      '#size' => 6,
      '#min' => 0,
      '#step' => 'any',
    ];

    $element['units'] = [
      '#type' => 'select',
      '#title' => $this->t('Units'),
      '#title_display' => 'invisible',
      '#default_value' => $units,
      '#options' => [
        'lb' => $this->t('Pounds'),
        'kg' => $this->t('Kilograms'),
        'oz' => $this->t('Ounces'),
        'g' => $this->t('Grams'),
      ],
    ];

    return $element;
  }

}
