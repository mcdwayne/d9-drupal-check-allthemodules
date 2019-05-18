<?php

namespace Drupal\Tests\commerce_klarna_payments\Unit\Request;

use Drupal\commerce_klarna_payments\Klarna\Request\Customer;
use Drupal\Tests\UnitTestCase;

/**
 * Customer request unit tests.
 *
 * @group commerce_klarna_payments
 * @coversDefaultClass \Drupal\commerce_klarna_payments\Klarna\Request\Customer
 */
class CustomerTest extends UnitTestCase {

  /**
   * @covers ::setOrganizationEntityType
   * @expectedException \InvalidArgumentException
   */
  public function testSetOrganizationException() {
    $customer = new Customer();
    $customer->setOrganizationEntityType('INVALID');
  }

  /**
   * Tests toArray() method.
   *
   * @covers ::toArray
   * @covers ::setBirthday
   * @covers ::setGender
   * @covers ::setLastFourSsn
   * @covers ::setSsn
   * @covers ::setType
   * @covers ::setVatId
   * @covers ::setOrganizationEntityType
   * @covers ::setOrganizationRegistrationId
   */
  public function testToArray() {
    $expected = [
      'date_of_birth' => '2018-01-01',
      'gender' => 'male',
      'last_four_ssn' => '1234',
      'national_identification_number' => '01011970-1234',
      'type' => 'Joo',
      'vat_id' => 'VAT id 12345',
      'organization_registration_id' => '123456789',
      'organization_entity_type' => 'OTHER',
    ];
    $customer = new Customer();
    $customer->setBirthday(new \DateTime($expected['date_of_birth']))
      ->setGender($expected['gender'])
      ->setLastFourSsn($expected['last_four_ssn'])
      ->setSsn($expected['national_identification_number'])
      ->setType($expected['type'])
      ->setVatId($expected['vat_id'])
      ->setOrganizationEntityType($expected['organization_entity_type'])
      ->setOrganizationRegistrationId($expected['organization_registration_id']);

    $this->assertEquals($expected, $customer->toArray());
  }

}
