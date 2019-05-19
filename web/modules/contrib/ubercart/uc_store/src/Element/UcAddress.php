<?php

namespace Drupal\uc_store\Element;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\uc_store\Address;
use Drupal\uc_store\AddressInterface;

/**
 * Provides a form element for Ubercart address input.
 *
 * @FormElement("uc_address")
 */
class UcAddress extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#required' => TRUE,
      '#hide' => [],
      '#process' => [
        [$class, 'processAddress'],
      ],
      '#attributes' => ['class' => ['uc-store-address-field']],
      '#theme_wrappers' => ['container'],
      '#hidden' => FALSE,
    ];
  }

  /**
   * Callback for the address field #process property.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   generic input element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   */
  public static function processAddress(&$element, FormStateInterface $form_state, &$complete_form) {
    $labels = [
      'first_name' => t('First name'),
      'last_name' => t('Last name'),
      'company' => t('Company'),
      'street1' => t('Street address'),
      'street2' => ' ',
      'city' => t('City'),
      'zone' => t('State/Province'),
      'country' => t('Country'),
      'postal_code' => t('Postal code'),
      'phone' => t('Phone number'),
      'email' => t('E-mail'),
    ];

    $element['#tree'] = TRUE;
    $config = \Drupal::config('uc_store.settings')->get('address_fields');
    /** @var \Drupal\uc_store\AddressInterface $value */
    $value = $element['#value'];
    $hide = array_flip($element['#hide']);
    $wrapper = Html::getClass('uc-address-' . $element['#name'] . '-zone-wrapper');
    $country_names = \Drupal::service('country_manager')->getEnabledList();

    // Force the selected country to a valid one, so the zone dropdown matches.
    if ($country_keys = array_keys($country_names)) {
      if (isset($value->country) && !isset($country_names[$value->country])) {
        $value->country = $country_keys[0];
      }
    }

    // Iterating on the Address object excludes non-public properties, which
    // is exactly what we want to do.
    $address = Address::create();
    foreach ($address as $field => $field_value) {
      switch ($field) {
        case 'country':
          if ($country_names) {
            $subelement = [
              '#type' => 'select',
              '#options' => $country_names,
              '#ajax' => [
                'callback' => [get_class(), 'updateZone'],
                'wrapper' => $wrapper,
                'progress' => [
                  'type' => 'throbber',
                ],
              ],
            ];
          }
          else {
            $subelement = [
              '#type' => 'hidden',
              '#required' => FALSE,
            ];
          }
          break;

        case 'zone':
          $subelement = [
            '#prefix' => '<div id="' . $wrapper . '">',
            '#suffix' => '</div>',
          ];

          $zones = $value->country ? \Drupal::service('country_manager')->getZoneList($value->country) : [];
          if ($zones) {
            natcasesort($zones);
            $subelement += [
              '#type' => 'select',
              '#options' => $zones,
              '#empty_value' => '',
              '#after_build' => [[get_class(), 'resetZone']],
            ];
          }
          else {
            $subelement += [
              '#type' => 'hidden',
              '#value' => '',
              '#required' => FALSE,
            ];
          }
          break;

        case 'postal_code':
          $subelement = [
            '#type' => 'textfield',
            '#size' => 10,
            '#maxlength' => 10,
          ];
          break;

        case 'phone':
          $subelement = [
            '#type' => 'tel',
            '#size' => 16,
            '#maxlength' => 32,
          ];
          break;

        case 'email':
          $subelement = [
            '#type' => 'email',
            '#size' => 16,
          ];
          break;

        default:
          $subelement = [
            '#type' => 'textfield',
            '#size' => 32,
          ];
      }

      // Copy JavaScript states from the parent element.
      if (isset($element['#states'])) {
        $subelement['#states'] = $element['#states'];
      }

      // Set common values for all address fields.
      $element[$field] = $subelement + [
        '#title' => $labels[$field],
        '#default_value' => $value->$field,
        '#access' => !$element['#hidden'] && !empty($config[$field]['status']) && !isset($hide[$field]),
        '#required' => $element['#required'] && !empty($config[$field]['required']),
        '#weight' => isset($config[$field]['weight']) ? $config[$field]['weight'] : 0,
      ];
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input !== FALSE) {
      return Address::create($input);
    }
    elseif ($element['#default_value'] instanceof AddressInterface) {
      return $element['#default_value'];
    }
    elseif (is_array($element['#default_value'])) {
      // @todo Remove when all callers supply objects.
      return Address::create($element['#default_value']);
    }
    else {
      return Address::create();
    }
  }

  /**
   * Ajax callback: updates the zone select box when the country is changed.
   */
  public static function updateZone($form, FormStateInterface $form_state) {
    $element = &$form;
    $triggering_element = $form_state->getTriggeringElement();
    foreach (array_slice($triggering_element['#array_parents'], 0, -1) as $field) {
      $element = &$element[$field];
    }
    return $element['zone'];
  }

  /**
   * Resets the zone dropdown when the country is changed.
   */
  public static function resetZone($element, FormStateInterface $form_state) {
    if (!isset($element['#options'][$element['#default_value']])) {
      $element['#value'] = $element['#empty_value'];
    }
    return $element;
  }

}
