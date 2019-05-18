<?php

namespace Drupal\geolocation_2gis\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'geolocation2gis_map' widget.
 *
 * @FieldWidget(
 *   id = "geolocation2gis_map",
 *   label = @Translation("Geolocation 2GIS Map"),
 *   field_types = {
 *     "geolocation2gis"
 *   }
 * )
 */
class Geolocation2gisMapWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $geo_items = [];
    if((isset($items[$delta]->lat)) && (isset($items[$delta]->lng))){
      $geo_items[] = [
        'lat' => $items[$delta]->lat,
        'lng' => $items[$delta]->lng,
        'description' => $items[$delta]->lat . ', ' . $items[$delta]->lng
      ];
    }

    $element['#type'] = 'fieldset';

    $element['lat'] = [
      '#type' => 'hidden',
      '#attributes' => ['id' => 'lat-2gis'],
      '#default_value' => (isset($items[$delta]->lat)) ? $items[$delta]->lat : NULL,
      '#required' => $this->fieldDefinition->isRequired(),
      '#prefix' => '<div id="map-2gis" style="width:100%; height:400px"></div>'
    ];

    $element['lng'] = [
      '#type' => 'hidden',
      '#attributes' => ['id' => 'lng-2gis'],
      '#default_value' => (isset($items[$delta]->lng)) ? $items[$delta]->lng : NULL,
      '#required' => $this->fieldDefinition->isRequired(),
    ];

    $element['#attached']['library'][] = 'geolocation_2gis/api-2gis';
    $element['#attached']['library'][] = 'geolocation_2gis/map-widget';
    $element['#attached']['drupalSettings']['locations'] = $geo_items;
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    return parent::massageFormValues($values, $form, $form_state);
  }

}
