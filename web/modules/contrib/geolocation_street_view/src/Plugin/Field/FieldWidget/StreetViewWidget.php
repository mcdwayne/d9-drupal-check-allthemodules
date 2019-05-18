<?php

namespace Drupal\geolocation_street_view\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\geolocation\Plugin\Field\FieldWidget\GeolocationGooglegeocoderWidget;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'geolocation_street_view' widget.
 *
 * @FieldWidget(
 *   id = "geolocation_street_view",
 *   label = @Translation("Geolocation Street View"),
 *   field_types = {
 *     "geolocation"
 *   }
 * )
 */
class StreetViewWidget extends GeolocationGooglegeocoderWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $canvas_id = $element['map_canvas']['#attributes']['id'];

    foreach (['heading', 'pitch', 'zoom'] as $key) {
      // Saved value.
      $value = isset($items[$delta]->data['google_street_view_pov'][$key]) ? (float) $items[$delta]->data['google_street_view_pov'][$key] : NULL;

      // Hidden Street View POV field.
      $element[$key] = [
        '#type' => 'hidden',
        '#default_value' => $value,
        '#attributes' => ['class' => ['geolocation-hidden-' . $key]],
      ];

      // Street View POV value in canvas.
      $element['map_canvas']['#attached']['drupalSettings']['geolocation']['widgetMaps'][$canvas_id][$key] = $value;
    }

    $element['map_canvas']['#attached']['library'] = ['geolocation_street_view/widget.street_view'];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $values = parent::massageFormValues($values, $form, $form_state);

    // Save Street View POV values in data.
    foreach ($values as $delta => $item_values) {
      if (strlen($item_values['heading']) && strlen($item_values['pitch']) && strlen($item_values['zoom'])) {
        $values[$delta]['data']['google_street_view_pov'] = [
          'heading' => $item_values['heading'],
          'pitch' => $item_values['pitch'],
          'zoom' => $item_values['zoom'],
        ];
      }
    }

    return $values;
  }

}
