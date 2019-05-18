<?php

namespace Drupal\Tests\commerce_klarna_payments\Unit\Request;

use Drupal\commerce_klarna_payments\Klarna\Request\Address;
use Drupal\Tests\UnitTestCase;

/**
 * Address request unit tests.
 *
 * @group commerce_klarna_payments
 * @coversDefaultClass \Drupal\commerce_klarna_payments\Klarna\Request\Address
 */
class AddressTest extends UnitTestCase {

  /**
   * Tests toArray() method.
   *
   * @covers \Drupal\commerce_klarna_payments\Klarna\Request\Address
   */
  public function testToArray() {
    $expected = [
      'title' => 'Test',
      'given_name' => 'Firstname',
      'family_name' => 'Lastname',
      'email' => 'test@example.com',
      'street_address' => 'Pasilankatu 5',
      'street_address2' => 'dsdsa',
      'postal_code' => '00180',
      'city' => 'Helsinki',
      'region' => 'Uusimaa',
      'phone' => '0401234567',
      'country' => 'FI',
      'organization_name' => 'Druid Oy',
      'attention' => 'Attention',
    ];
    $address = new Address();
    $address->setTitle($expected['title'])
      ->setGivenName($expected['given_name'])
      ->setFamilyName($expected['family_name'])
      ->setEmail($expected['email'])
      ->setStreetAddress($expected['street_address'])
      ->setStreetAddress2($expected['street_address2'])
      ->setPostalCode($expected['postal_code'])
      ->setCity($expected['city'])
      ->setRegion($expected['region'])
      ->setPhone($expected['phone'])
      ->setCountry($expected['country'])
      ->setOrganization($expected['organization_name'])
      ->setAttention($expected['attention']);

    $this->assertEquals($expected, $address->toArray());
  }

}
