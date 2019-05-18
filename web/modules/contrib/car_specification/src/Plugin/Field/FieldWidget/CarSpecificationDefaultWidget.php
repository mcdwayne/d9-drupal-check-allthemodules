<?php

namespace Drupal\car_specification\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'car_specification_default_widget' widget.
 *
 * @FieldWidget(
 *   id = "car_specification_default_widget",
 *   label = @Translation("Car specification default widget"),
 *   field_types = {
 *     "car_specification"
 *   }
 * )
 */
class CarSpecificationDefaultWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Car Library.
    $form['#attached']['library'][] = 'car_specification/car_specification.carselect';
    // Car Years.
    $element['car_years'] = [
      '#type' => 'select',
      '#title' => $this->t('Car Years'),
      '#name' => $this->t('car-years'),
      '#id' => $this->t('car-years'),
      // Set here the current value for this field, or a default value (or
      // null) if there is no a value.
      '#default_value' => isset($items[$delta]->car_years) ?
      $items[$delta]->car_years : NULL,

      '#empty_value' => '',
    ];

    // Car Makes.
    $element['car_makes'] = [
      '#type' => 'select',
      '#title' => $this->t('Car Makes'),
      '#name' => $this->t('car-makes'),
      '#id' => $this->t('car-makes'),
      // Set here the current value for this field, or a default value (or
      // null) if there is no a value.
      '#default_value' => isset($items[$delta]->car_makes) ?
      $items[$delta]->car_makes : NULL,

      '#empty_value' => '',
    ];

    // Car Models.
    $element['car_models'] = [
      '#type' => 'select',
      '#title' => $this->t('Car Models'),
      '#name' => $this->t('car-models'),
      '#id' => $this->t('car-models'),
      // Set here the current value for this field, or a default value (or
      // null) if there is no a value.
      '#default_value' => isset($items[$delta]->car_models) ?
      $items[$delta]->car_models : NULL,

      '#empty_value' => '',
    ];

    // Car Model Trims.
    $element['car_model_trims'] = [
      '#type' => 'select',
      '#title' => $this->t('Car Model Trims'),
      '#name' => $this->t('car-model-trims'),
      '#id' => $this->t('car-model-trims'),
      // Set here the current value for this field, or a default value (or
      // null) if there is no a value.
      '#default_value' => isset($items[$delta]->car_model_trims) ?
      $items[$delta]->car_model_trims : NULL,
      '#empty_value' => '',
      '#suffix' => '<div id="cq-show-data" class="button">Show</div>',
    ];
    return $element;
  }

}
