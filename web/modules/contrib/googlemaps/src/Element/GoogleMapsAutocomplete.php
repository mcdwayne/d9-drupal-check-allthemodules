<?php

namespace Drupal\googlemaps\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\googlemaps\Entity\GoogleMap;

/**
 * Provides a "googlemaps_autocomplete" element.
 *
 * @RenderElement("googlemaps_autocomplete")
 */
class GoogleMapsAutocomplete extends EntityAutocomplete {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $info['#target_type'] = GoogleMap::ENTITY_TYPE;

    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    $storage = \Drupal::entityTypeManager()->getStorage($element['#target_type']);

    if (is_string($element['#default_value'])) {
      $element['#default_value'] = $storage->load($element['#default_value']);
    }
    elseif (is_array($element['#default_value']) && !empty(array_filter($element['#default_value'], 'is_string'))) {
      $element['#default_value'] = $storage->loadMultiple($element['#default_value']);
    }

    return parent::valueCallback($element, $input, $form_state);
  }

}
