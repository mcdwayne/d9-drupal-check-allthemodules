<?php

namespace Drupal\loqate\Element;

use Drupal\webform\Element\WebformCompositeBase;

/**
 * Provides a webform element for an address element.
 *
 * @FormElement("webform_address_loqate")
 */
class WebformAddressLoqate extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return parent::getInfo() + [
      '#theme' => 'webform_composite_address',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderCompositeFormElement($element) {
    // Getting the configuration key.
    $loqateApikey = \Drupal::config('loqate.loqateapikeyconfig')->get('loqate_api_key');

    $element = parent::preRenderCompositeFormElement($element);
    $element['#wrapper_attributes']['class'][] = 'address-lookup';
    $element['#wrapper_attributes']['class'][] = 'address-lookup--initial';
    $element['#wrapper_attributes']['data-key'] = $element['#webform_key'];
    $element['#attached'] = [
      'library' => [
        'loqate/loqate',
      ],
      'drupalSettings' => [
        'loqate' => [
          'loqate' => [
            'key' => $loqateApikey,
          ],
        ],
      ],
    ];

    foreach (array_keys($element['#webform_composite_elements']) as $key) {
      if ($key !== 'postal_code') {
        $element[$key]['#wrapper_attributes']['class'][] = 'address-lookup__field';
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element) {

    $elements = [];

    $elements['address'] = [
      '#type' => 'textfield',
      '#title' => t('Address'),
    ];

    $elements['address_2'] = [
      '#type' => 'textfield',
      '#title' => t('Address 2'),
    ];

    $elements['city'] = [
      '#type' => 'textfield',
      '#title' => t('City/Town'),
    ];

    $elements['region'] = [
      '#type' => 'textfield',
      '#title' => t('Region'),
    ];

    $elements['state_province'] = [
      '#type' => 'select',
      '#title' => t('State/Province'),
      '#options' => 'state_province_names',
      '#empty_option' => '',
    ];

    $elements['postal_code'] = [
      '#type' => 'textfield',
      '#title' => t('Zip/Postal Code'),
    ];

    $elements['country'] = [
      '#type' => 'select',
      '#title' => t('Country'),
      '#options' => 'country_names',
      '#empty_option' => '',
    ];

    return $elements;
  }

}
