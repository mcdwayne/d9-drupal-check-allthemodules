<?php

namespace Drupal\Tests\uc_store\Traits;

use Drupal\uc_store\Address;

/**
 * Utility functions to provide addresses for test purposes.
 *
 * This trait can only be used in classes which already use
 * RandomGeneratorTrait. RandomGeneratorTrait is used in all
 * the PHPUnit and Simpletest base classes.
 */
trait AddressTestTrait {

  /**
   * Creates an address object based on default settings.
   *
   * This function is a wrapper around Address::create() which serves to
   * produce reasonable-looking random addresses for randomly selected countries
   * that are enabled in Ubercart. The goal is to have addresses which are
   * recognizable as addresses, not just some collection of random strings. The
   * address fields may be fully or partially filled in - the unfilled fields
   * will be populated with random values.
   *
   * @param array $settings
   *   (optional) An associative array of settings to change from the defaults,
   *   keys are address properties. For example, 'city' => 'London'.
   *
   * @return \Drupal\uc_store\AddressInterface
   *   Address object.
   */
  protected function createAddress(array $settings = []) {
    $street = array_flip([
      'Street',
      'Avenue',
      'Place',
      'Way',
      'Road',
      'Boulevard',
      'Court',
    ]);
    $org = array_flip(['Inc.', 'Ltd.', 'LLC', 'GmbH', 'PLC', 'SE']);

    // Populate any fields that weren't passed in $settings.
    $values = $settings + [
      'first_name'  => $this->randomMachineName(6),
      'last_name'   => $this->randomMachineName(12),
      'company'     => $this->randomMachineName(10) . ', ' . array_rand($org),
      'street1'     => mt_rand(10, 1000) . ' ' .
                       $this->randomMachineName(10) . ' ' . array_rand($street),
      'street2'     => 'Suite ' . mt_rand(100, 999),
      'city'        => $this->randomMachineName(10),
      'postal_code' => (string) mt_rand(10000, 99999),
      'phone'       => '(' . mt_rand(100, 999) . ') ' .
                       mt_rand(100, 999) . '-' . mt_rand(0, 9999),
      'email'       => $this->randomMachineName(8) . '@example.com',
    ];

    // Set the country if it isn't set already.
    $country_id = array_rand(\Drupal::service('country_manager')->getEnabledList());
    $values += ['country' => $country_id];

    // Don't try to set the zone unless the country has zones!
    $zone_list = \Drupal::service('country_manager')->getZoneList($values['country']);
    if (!empty($zone_list)) {
      $values += ['zone' => array_rand($zone_list)];
    }

    // Create an Address object with these values.
    $address = Address::create($values);

    return $address;
  }

}
