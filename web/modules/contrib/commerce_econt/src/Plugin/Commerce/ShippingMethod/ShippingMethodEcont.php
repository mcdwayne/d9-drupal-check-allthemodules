<?php

namespace Drupal\commerce_econt\Plugin\Commerce\ShippingMethod;

use Drupal\commerce_shipping\Plugin\Commerce\ShippingMethod\ShippingMethodBase;
use Drupal\commerce_shipping\Entity\ShipmentInterface as ShipInterface;

/**
 * Provides the base class for shipping methods.
 */
abstract class ShippingMethodEcont extends ShippingMethodBase implements ShippingMethodEcontInterface {


  /**
   * Implements getDefaultStoreData() from ShippingMethodEcontInterface
   *
   * @return array
   *   The array with all nessasary data fields.
   */
  public function getDefaultStoreData() {
    $defaultStoreResolver = \Drupal::service('commerce_store.default_store_resolver');
    $defaultStoreData = $defaultStoreResolver->resolve();
    $addressData = $defaultStoreData->getAddress();

    return ['country_code' => $addressData->country_code,
            'locality' => $addressData->locality,
            'postal_code' => $addressData->postal_code,
            'address_line1' => $addressData->address_line1,
            'address_line2' => $addressData->address_line2,
            'email' => $defaultStoreData->getEmail(),
            'store_name' => $defaultStoreData->getName()
           ];
  }

  /**
   * Implements getShippngData() from ShippingMethodEcontInterface
   *
   * @return array
   *   The array with all nessasary data fields.
   */
  public function getShippingData(ShipInterface $shipment){
    
    $weight = $shipment->getWeight()->getNumber();
    $total_price = $shipment->getOrder()->getTotalPrice()->getNumber();
    $shipping_profile = $shipment->getShippingProfile();
    $shipping_info_data = $shipping_profile->toArray();

    $shipping_info_data['address'][0]['address_line1'] = str_replace('Econt Office: ', '', $shipping_info_data['address'][0]['address_line1']);

    $shipping_address = array();
    $street_name = '';
    $street_num = '';
    preg_match('/(.+)(\s)(.+)/', $shipping_info_data['address'][0]['address_line1'], $shipping_address);

    if(!empty($shipping_address)) {
      $street_name = $shipping_address[1];
      $street_num = $shipping_address[3];
    } else {
      $street_name = $shipping_info_data['address'][0]['address_line1'];
    }

    $phone_match = array();
    $phone = '';
    preg_match('/^\d+|[+]\d+$/', $shipping_info_data['address'][0]['address_line2'], $phone_match);

    if(!empty($phone_match)) {
      $phone = $phone_match[0];
    }

    return ['weight' => $weight,
            'total_price' => $total_price,
            'country_code' => $shipping_info_data['address'][0]['country_code'],
            'first_name' => $shipping_info_data['address'][0]['given_name'],
            'family_name' => $shipping_info_data['address'][0]['family_name'],
            'organization' => $shipping_info_data['address'][0]['organization'],
            'phone' => $phone,
            'locality' => $shipping_info_data['address'][0]['locality'],
            'postal_code' => $shipping_info_data['address'][0]['postal_code'],
            'street_name' => $street_name,
            'street_num' => $street_num,
           ];
  }
}
