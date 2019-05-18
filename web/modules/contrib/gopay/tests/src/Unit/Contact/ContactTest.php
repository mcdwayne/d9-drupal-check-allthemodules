<?php

namespace Drupal\Tests\gopay\Unit\Contact;

use Drupal\Tests\UnitTestCase;
use Drupal\gopay\Contact\Contact;

/**
 * @coversDefaultClass \Drupal\gopay\Contact\Contact
 * @group gopay
 */
class ContactTest extends UnitTestCase {

  /**
   * Test default values.
   */
  public function testDefaultValues() {
    $expected_config = [
      'first_name' => NULL,
      'last_name' => NULL,
      'email' => NULL,
      'phone_number' => NULL,
      'city' => NULL,
      'street' => NULL,
      'postal_code' => NULL,
      'country_code' => NULL,
    ];

    $contact = new Contact();
    $contact_config = $contact->toArray();

    $this->assertArrayEquals($expected_config, $contact_config);
  }

  /**
   * Test setting of all values. In cascade style.
   */
  public function testAllSetters() {
    $expected_config = [
      'first_name' => 'Foo',
      'last_name' => 'Bar',
      'email' => 'foo@bar.baz',
      'phone_number' => '123456',
      'city' => 'City of choice',
      'street' => 'Some street',
      'postal_code' => '100',
      'country_code' => 'CODE',
    ];

    $contact_config = (new Contact())
      ->setFirstName('Foo')
      ->setLastName('Bar')
      ->setEmail('foo@bar.baz')
      ->setPhoneNumber(123456)
      ->setCity('City of choice')
      ->setStreet('Some street')
      ->setPostalCode(100)
      ->setCountryCode('CODE')
      ->toArray();

    $this->assertArrayEquals($expected_config, $contact_config);
  }

}
