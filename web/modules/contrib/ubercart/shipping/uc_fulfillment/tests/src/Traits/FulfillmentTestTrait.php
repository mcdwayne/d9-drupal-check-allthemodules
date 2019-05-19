<?php

namespace Drupal\Tests\uc_fulfillment\Traits;

/**
 * Utility functions for fulfillment test purposes.
 *
 * This trait can only be used in classes which already use
 * RandomGeneratorTrait. RandomGeneratorTrait is used in all
 * the PHPUnit and Simpletest base classes.
 */
trait FulfillmentTestTrait {

  /**
   * Helper function to fill-in required fields on the shipment page.
   *
   * Copied in part from UbercartBrowserTestBase::populateCheckoutForm().
   *
   * @param array $edit
   *   The form-values array to which to add required fields.
   *
   * @return array
   *   The values array ready to pass to the checkout page.
   */
  protected function populateShipmentForm(array $edit = []) {
    $street = array_flip([
      'Street',
      'Avenue',
      'Place',
      'Way',
      'Road',
      'Boulevard',
      'Court',
    ]);

    // Fill in the address details.
    foreach (['pickup', 'delivery'] as $pane) {
      $prefix = $pane . '_address';
      $key = $prefix . '[country]';
      $country_id = isset($edit[$key]) ? $edit[$key] : \Drupal::config('uc_store.settings')->get('address.country');
      $country = \Drupal::service('country_manager')->getCountry($country_id);

      $edit += [
        $prefix . '[first_name]' => $this->randomMachineName(6),
        $prefix . '[last_name]' => $this->randomMachineName(12),
        $prefix . '[company]' => $this->randomMachineName(10) . ', Inc.',
        $prefix . '[street1]' => mt_rand(10, 1000) . ' ' . $this->randomMachineName(10) . ' ' . array_rand($street),
        $prefix . '[street2]' => 'Suite ' . mt_rand(100, 999),
        $prefix . '[city]' => $this->randomMachineName(10),
        $prefix . '[postal_code]' => (string) mt_rand(10000, 99999),
        $prefix . '[country]' => $country_id,
      ];

      // Don't try to set the zone unless the store country has zones!
      if (!empty($country->getZones())) {
        $edit += [
          $prefix . '[zone]' => array_rand($country->getZones()),
        ];
      }
    }

    // Fill in the shipping details.
    $edit += [
      'packages[1][pkg_type]' => 'envelope',
      'packages[1][declared_value]' => '1234.56',
      'packages[1][tracking_number]' => '4-8-15-16-23-42',
      'packages[1][weight][weight]' => '3',
      'packages[1][weight][units]' => array_rand(array_flip(['lb', 'kg', 'oz', 'g'])),
      'packages[1][dimensions][length]' => '1',
      'packages[1][dimensions][width]' => '1',
      'packages[1][dimensions][height]' => '1',
      'packages[1][dimensions][length]' => '1',
      'packages[1][dimensions][units]' => array_rand(array_flip(['in', 'ft', 'cm', 'mm'])),
      'carrier' => 'FedEx',
      'accessorials' => 'Standard Overnight',
      'transaction_id' => 'THX1138',
      'tracking_number' => '1234567890ABCD',
      'ship_date[date]' => '1985-10-26',
      'expected_delivery[date]' => '2015-10-21',
      'cost' => '12.34',
    ];

    return $edit;
  }

}
