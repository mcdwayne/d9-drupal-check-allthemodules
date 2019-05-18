<?php

namespace Drupal\contacts_events\Element;

use Drupal\commerce_price\Element\Price;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a price form element for use in a price map.
 *
 * @FormElement("price_map_item")
 */
class PriceMapItem extends Price {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#booking_window' => NULL,
      '#class' => NULL,
    ] + parent::getInfo();
  }

  /**
   * {@inheritdoc}
   */
  public static function processElement(array $element, FormStateInterface $form_state, array &$complete_form) {
    // Pass on for Price to do it's thing.
    $element = parent::processElement($element, $form_state, $complete_form);

    // Add in out booking window and class values.
    $element['booking_window'] = [
      '#type' => 'value',
      '#value' => $element['#booking_window'],
    ];
    $element['class'] = [
      '#type' => 'value',
      '#value' => $element['#class'],
    ];

    return $element;
  }

}
