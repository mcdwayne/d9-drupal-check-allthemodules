<?php

namespace Drupal\address_phonenumber\Element;

use Drupal\address\Element\Address;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an adress_phone_number form element.
 *
 * Usage example:
 * @code
 * $form['address_phone_number_item'] = [
 *   '#type' => 'address_phone_number_item',
 *   '#default_value' => [
 *     'contact' => '9999999999',
 *   ],
 * ];
 * @endcode
 *
 * @FormElement("address_phone_number_item")
 */
class AddressPhoneNumber extends Address {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return $info = parent::getInfo();
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if (is_array($input)) {
      return $input;
    }
    else {
      if (!is_array($element['#default_value'])) {
        $element['#default_value'] = [];
      }
      // Initialize properties.
      $properties = [
        'given_name', 'additional_name', 'family_name', 'organization',
        'address_line1', 'address_line2', 'postal_code', 'sorting_code',
        'dependent_locality', 'locality', 'administrative_area',
        'country_code', 'langcode', 'address_phonenumber',
      ];
      foreach ($properties as $property) {
        if (!isset($element['#default_value'][$property])) {
          $element['#default_value'][$property] = NULL;
        }
      }

      return $element['#default_value'];
    }
  }

}
