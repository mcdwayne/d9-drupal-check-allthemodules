<?php

namespace Drupal\Tests\uc_store\Functional;

use Drupal\uc_store\Address;
use Drupal\Tests\uc_store\Traits\AddressTestTrait;

/**
 * Tests the creation and comparison of addresses.
 *
 * @group ubercart
 */
class AddressTest extends UbercartBrowserTestBase {
  use AddressTestTrait;

  /**
   * Typical Address objects to test.
   *
   * Do not modify these in test functions! Test functions may run in any order
   * or simultaneously, leading to unpredictable results if these objects are
   * modified by a test. Instead, clone these objects and operate on the clone.
   *
   * @var \Drupal\uc_store\Address[]
   */
  protected $testAddresses = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a random address object for use in tests.
    $this->testAddresses[] = $this->createAddress();

    // Create a specific address object for use in tests.
    $settings = [
      'first_name'  => 'Elmo',
      'last_name'   => 'Monster',
      'company'     => 'CTW, Inc.',
      'street1'     => '123 Sesame Street',
      'street2'     => '',
      'city'        => 'New York',
      'zone'        => 'NY',
      'country'     => 'US',
      'postal_code' => '10010',
      'phone'       => '1234567890',
      'email'       => 'elmo@ctw.org',
    ];
    $this->testAddresses[] = $this->createAddress($settings);
  }

  /**
   * Tests formatting of addresses.
   */
  public function testAddressFormat() {
    $address = Address::create();
    $address->setCountry(NULL);
    $formatted = (string) $address;
    $expected = '';
    $this->assertEquals($formatted, $expected, 'Formatted empty address is an empty string.');

    $address = clone($this->testAddresses[1]);

    // Expected format depends on the store country.
    $store_country = \Drupal::config('uc_store.settings')->get('address.country');

    $formatted = (string) $address;
    if ($store_country == 'US') {
      $expected = "CTW, INC.<br>\nELMO MONSTER<br>\n123 SESAME STREET<br>\nNEW YORK, NY 10010";
    }
    else {
      $expected = "CTW, INC.<br>\nELMO MONSTER<br>\n123 SESAME STREET<br>\nNEW YORK, NY 10010<br>\nUNITED STATES";
    }
    $this->assertEquals($formatted, $expected, 'Formatted address matches expected value.');

    $address->setCity('Victoria');
    $address->setZone('BC');
    $address->setCountry('CA');
    $formatted = (string) $address;
    if ($store_country == 'CA') {
      $expected = "CTW, INC.<br>\nELMO MONSTER<br>\n123 SESAME STREET<br>\nVICTORIA BC  10010";
    }
    else {
      $expected = "CTW, INC.<br>\nELMO MONSTER<br>\n123 SESAME STREET<br>\nVICTORIA BC  10010<br>\nCANADA";
    }
    $this->assertEquals($formatted, $expected, 'Formatted address with non-default country matches expected value.');
  }

  /**
   * Tests comparison of addresses.
   */
  public function testAddressComparison() {
    $this->assertTrue((string) $this->testAddresses[0]);
    $this->assertTrue((string) $this->testAddresses[1]);

    // Use randomly generated address first.
    $address = clone($this->testAddresses[0]);

    // Modify phone number and test equality.
    $address->setPhone('this is not a valid phone number');
    $this->assertTrue(
      $this->testAddresses[0]->isSamePhysicalLocation($address),
      'Physical address comparison ignores non-physical fields.'
    );

    // Use a specific address modified to our needs.
    $original = clone($this->testAddresses[1]);
    $original->setCity('Victoria');
    $original->setZone('BC');
    $original->setCountry('CA');

    // Address to modify.
    $address = clone($original);

    // Modify city and test equality.
    $address->setCity('vIcToRia');
    $this->assertTrue(
      $address->isSamePhysicalLocation($original),
      'Case-insensitive address comparison works.'
    );

    // Modify city and test equality.
    $address->setCity('		vic toria ');
    $this->assertTrue(
      $address->isSamePhysicalLocation($original),
      'Whitespace-insensitive address comparison works.'
    );

  }

}
