<?php

namespace Drupal\address_usps;

use Drupal\Core\Language\LanguageInterface;

/**
 * AddressUSPSHelper class.
 */
final class AddressUSPSHelper {
  // Config keys.
  const CONFIG_USPS_USERNAME = 'service_username';
  const US_ADDRESS_LIMIT_WIDGET_MESSAGE = 'USPS validation works only with United States.';
  const US_ADDRESS_US_COUNTRY_NOT_SELECTED = 'United States is not enabled in field settings.';
  const ADDRESS_NOT_FOUND_MESSAGE = 'Address was not found in USPS database.';

  const HOOK_ELEMENT_ALTER = 'address_usps_element';
  const HOOK_ELEMENT_VALIDATION_ALTER = 'address_usps_element_validation';
  const HOOK_ELEMENT_AJAX_RESPONSE_PRE_ALTER = 'address_usps_element_ajax_response_pre';
  const HOOK_ELEMENT_AJAX_RESPONSE_ALTER = 'address_usps_element_ajax_response';

  /**
   * Render address element as HTML by element value.
   *
   * @param array $element_value
   *   Element value.
   *
   * @return array
   *   Render array with '#type' => 'address_plain'
   */
  public static function renderAddressElementByValue(array $element_value) {
    $full_country_list = \Drupal::service('address.country_repository')
      ->getList();

    return [
      '#theme' => 'address_plain',
      '#given_name' => isset($element_value['given_name']) ? $element_value['given_name'] : '',
      '#additional_name' => isset($element_value['additional_name']) ? $element_value['additional_name'] : '',
      '#family_name' => isset($element_value['family_name']) ? $element_value['family_name'] : '',
      '#organization' => isset($element_value['organization']) ? $element_value['organization'] : '',
      '#address_line1' => isset($element_value['address_line1']) ? $element_value['address_line1'] : '',
      '#address_line2' => isset($element_value['address_line2']) ? $element_value['address_line2'] : '',
      '#postal_code' => isset($element_value['postal_code']) ? $element_value['postal_code'] : '',
      '#sorting_code' => isset($element_value['sorting_code']) ? $element_value['sorting_code'] : '',
      '#administrative_area' => isset($element_value['administrative_area']) ? $element_value['administrative_area'] : '',
      '#locality' => isset($element_value['locality']) ? $element_value['locality'] : '',
      '#dependent_locality' => isset($element_value['dependent_locality']) ? $element_value['dependent_locality'] : '',
      '#country' => [
        'code' => 'US',
        'name' => $full_country_list['US'],
      ],
      '#cache' => [
        'contexts' => [
          'languages:' . LanguageInterface::TYPE_INTERFACE,
        ],
      ],
    ];
  }

}
