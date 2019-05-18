<?php

namespace Drupal\geolocation_2gis\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'geolocation2gis_latlng' widget.
 *
 * @FieldWidget(
 *   id = "geolocation2gis_latlng",
 *   label = @Translation("Geolocation2gis Lat/Lng"),
 *   field_types = {
 *     "geolocation2gis"
 *   }
 * )
 */
class Geolocation2gisLatlngWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $element['#type'] = 'fieldset';

    $element['lat'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Latitude'),
      '#default_value' => (isset($items[$delta]->lat)) ? $items[$delta]->lat : NULL,
      '#empty_value' => '',
      '#maxlength' => 255,
      '#required' => $this->fieldDefinition->isRequired(),
    ];

    $lat_example = $element['lat']['#default_value'] ?: '52.47879';

    $element['lat']['#description'] = $this->t('Enter in decimal %decimal format', [
      '%decimal' => $lat_example,
    ]);

    $element['lng'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Longitude'),
      '#empty_value' => '',
      '#default_value' => (isset($items[$delta]->lng)) ? $items[$delta]->lng : NULL,
      '#maxlength' => 255,
      '#required' => $this->fieldDefinition->isRequired(),
    ];

    $lng_example = $element['lng']['#default_value'] ?: '104.010677';

    $element['lng']['#description'] = $this->t('Enter in decimal %decimal format', [
      '%decimal' => $lng_example,
    ]);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    return parent::massageFormValues($values, $form, $form_state);
  }

}
