<?php

namespace Drupal\address_autocomplete_gmaps\Element;

use Drupal\address\Element\Address;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an address form element.
 *
 * Usage example:
 * @code
 * $form['address'] = [
 *   '#type' => 'address',
 *   '#default_value' => [
 *     'given_name' => 'John',
 *     'family_name' => 'Smith',
 *     'organization' => 'Google Inc.',
 *     'address_line1' => '1098 Alta Ave',
 *     'postal_code' => '94043',
 *     'locality' => 'Mountain View',
 *     'administrative_area' => 'CA',
 *     'country_code' => 'US',
 *     'langcode' => 'en',
 *   ],
 *   '#available_countries' => ['DE', 'FR'],
 * ];
 * @endcode
 *
 * @FormElement("address")
 */
class AddressAutocomplete extends Address {

  /**
   * {@inheritdoc}
   */
  protected static function addressElements(array $element, array $value) {
    $element = parent::addressElements($element, $value);
    $classes = [
      'country_code' => 'country',
      'administrative_area' => 'state',
      'locality' => 'city',
      'dependent_locality' => 'subcity',
      'postal_code' => 'zip',
      'sorting_code' => 'sorting',
      'address_line1' => 'street',
      'address_line2' => 'street2',
    ];
    foreach (array_keys($element['#default_value']) as $name) {
      if (isset($element[$name]) && isset($classes[$name])) {
        $class = isset($element[$name]['#attributes']['class']) ? $element[$name]['#attributes']['class'] : [];
        $class[] = $classes[$name];
        $class[] = 'initial-address-field';
        $element[$name]['#attributes']['class'] = $class;
      }
    }

    // Disable country ajax field change
    //unset($element['country_code']['#ajax']);

    $element['#attached']['drupalSettings']['addressAutocomplete']['availableCountries'] = $element['#available_countries'];

    // Add hidden fields to be poppulated with Gmaps values
    $element['address_components'] = [
      'g_floor' => [
        '#type' => 'hidden',
        '#default_value' => NULL,
        '#attributes' => [
          'data-name' => ['g-floor']
        ],
      ],
      'g_street_number' => [
        '#type' => 'hidden',
        '#default_value' => NULL,
        '#attributes' => [
          'data-name' => ['g-street-number']
        ],
      ],
      'g_route' => [
        '#type' => 'hidden',
        '#default_value' => NULL,
        '#attributes' => [
          'data-name' => ['g-route']
        ],
      ],
      'g_locality' => [
        '#type' => 'hidden',
        '#default_value' => NULL,
        '#attributes' => [
          'data-name' => ['g-locality']
        ],
      ],
      'g_sublocality' => [
        '#type' => 'hidden',
        '#default_value' => NULL,
        '#attributes' => [
          'data-name' => ['g-sublocality']
        ],
      ],
      'g_sublocality_level_1' => [
        '#type' => 'hidden',
        '#default_value' => NULL,
        '#attributes' => [
          'data-name' => ['g-sublocality-level-1']
        ],
      ],
      'g_administrative_area_level_2' => [
        '#type' => 'hidden',
        '#default_value' => NULL,
        '#attributes' => [
          'data-name' => ['g-administrative-area-level-2']
        ],
      ],
      'g_administrative_area_level_1' => [
        '#type' => 'hidden',
        '#default_value' => NULL,
        '#attributes' => [
          'data-name' => ['g-administrative-area-level-1']
        ],
      ],
      'g_country' => [
        '#type' => 'hidden',
        '#default_value' => NULL,
        '#attributes' => [
          'data-name' => ['g-country']
        ],
      ],
      'g_postal_code' => [
        '#type' => 'hidden',
        '#default_value' => NULL,
        '#attributes' => [
          'data-name' => ['g-postal-code']
        ],
      ],
      'g_neighborhood' => [
        '#type' => 'hidden',
        '#default_value' => NULL,
        '#attributes' => [
          'data-name' => ['g-neighborhood']
        ],
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function ajaxRefresh(array $form, FormStateInterface $form_state) {
    $address_element = parent::ajaxRefresh($form, $form_state);

    $used_fields = [
      'administrativeArea' => 'administrative_area',
      'locality' => 'locality',
      'dependentLocality' => 'dependent_locality',
      'postalCode' => 'postal_code',
      'sortingCode' => 'sorting_code',
      'addressLine1' => 'address_line1',
      'addressLine2' => 'address_line2',
      'organization' => 'organization',
      'givenName' => 'given_name' ,
      'additionalName' => 'additional_name',
      'familyName' => 'family_name',
    ];
    $enabled_fields = array_intersect_key($used_fields, array_filter($address_element['#used_fields']));
    $enabled_fields['country_code'] = 'country_code';

    $g_fields = [
      'g_floor' => 'floor',
      'g_street_number' => 'street_number',
      'g_route' => 'address_line1',
      'g_locality' => 'locality',
      'g_sublocality' => 'dependent_locality',
      'g_sublocality_level_1' => 'sublocality_level_1',
      'g_administrative_area_level_2' => 'administrative_area_level_2',
      'g_administrative_area_level_1' => 'administrative_area',
      'g_country' => 'country_code',
      'g_postal_code' => 'postal_code',
      'g_neighborhood' => 'neighborhood',
    ];

    foreach ($address_element['#value']['address_components'] as $key => $value) {
      $field = $g_fields[$key];
      if (isset($address_element[$field])) {
        $address_element[$field]['#value'] = '';
        if ($value) {
          $address_element[$field]['#value'] = $value;
          // The street field in any case should be shown.
          if ($g_fields[$key] == 'address_line1') {
            $address_element[$field]['#description'] = t('Please, manually add an apartment number to the street address, if any.');
          }
          else {
            $address_element[$field]['#attributes']['class'][] = 'address-autocomplete-component--hidden';
            unset($address_element[$field]['#title']);
          }
        }
      }
      $address_element['#value']['address_components'][$key] = '';
      $address_element['address_components'][$key]['#value'] = '';
      $address_element['#value'][$field] = '';
    }

    return $address_element;
  }

}
