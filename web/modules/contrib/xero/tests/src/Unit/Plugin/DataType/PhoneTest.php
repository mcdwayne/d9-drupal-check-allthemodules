<?php

namespace Drupal\Tests\xero\Unit\Plugin\DataType;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\xero\TypedData\Definition\PhoneDefinition;
use Drupal\xero\Plugin\DataType\Phone;
use Drupal\Core\TypedData\Plugin\DataType\StringData;

/**
 * Assert setting and getting Phone properties.
 *
 * @coversDefaultClass \Drupal\xero\Plugin\DataType\Phone
 * @group Xero
 */
class PhoneTest extends TestBase {

  const XERO_TYPE = 'xero_phone';
  const XERO_TYPE_CLASS = '\Drupal\xero\Plugin\DataType\Phone';
  const XERO_DEFINITION_CLASS = '\Drupal\xero\TypedData\Definition\PhoneDefinition';

  protected $phone;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create data type.
    $type_class = self::XERO_TYPE_CLASS;
    $this->phone = new $type_class($this->dataDefinition, self::XERO_TYPE);
  }

  /**
   * Test getPropertyDefinitions.
   */
  public function testPropertyDefinitions() {
    $properties = $this->phone->getProperties();

    $this->assertArrayHasKey('PhoneType', $properties);
    $this->assertArrayHasKey('PhoneNumber', $properties);
    $this->assertArrayHasKey('PhoneAreaCode', $properties);
    $this->assertArrayHasKey('PhoneCountryCode', $properties);
  }

  /**
   * Test getPhone method.
   */
  public function testGetPhoneNumber() {
    $string_def = DataDefinition::create('string');
    $country = new StringData($string_def);
    $area = new StringData($string_def);
    $number = new StringData($string_def);

    $this->typedDataManager->expects($this->any())
      ->method('getPropertyInstance')
      ->with($this->phone, $this->callback(function($subject) {
        return in_array($subject, array('PhoneCountryCode', 'PhoneAreaCode', 'PhoneNumber'));
      }))
      ->will($this->onConsecutiveCalls($country, $area, $number));

    $this->phone->set('PhoneCountryCode', '01');
    $this->phone->set('PhoneAreaCode', '805');
    $this->phone->set('PhoneNumber', '255-8542');

    $this->assertEquals('01-805-255-8542', $this->phone->getPhone());
  }
}
