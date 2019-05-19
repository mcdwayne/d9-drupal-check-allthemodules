<?php

namespace Drupal\uc_product\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the Ubercart dimensions widget.
 *
 * @FieldWidget(
 *   id = "uc_dimensions",
 *   label = @Translation("Dimensions"),
 *   field_types = {
 *     "uc_dimensions",
 *   }
 * )
 */
class UcDimensionsWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $length = isset($items[$delta]->length) ? $items[$delta]->length : 0;
    $width = isset($items[$delta]->width) ? $items[$delta]->width : 0;
    $height = isset($items[$delta]->height) ? $items[$delta]->height : 0;
    $units = isset($items[$delta]->units) ? $items[$delta]->units : \Drupal::config('uc_store.settings')->get('length.units');

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

    $element['length'] = [
      '#type' => 'number',
      '#title' => $this->t('Length'),
      '#default_value' => $length,
      '#size' => 6,
      '#min' => 0,
      '#step' => 'any',
    ];

    $element['width'] = [
      '#type' => 'number',
      '#title' => $this->t('Width'),
      '#default_value' => $width,
      '#size' => 6,
      '#min' => 0,
      '#step' => 'any',
    ];

    $element['height'] = [
      '#type' => 'number',
      '#title' => $this->t('Height'),
      '#default_value' => $height,
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
        'in' => $this->t('Inches'),
        'ft' => $this->t('Feet'),
        'cm' => $this->t('Centimeters'),
        'mm' => $this->t('Millimeters'),
      ],
    ];

    return $element;
  }

}
