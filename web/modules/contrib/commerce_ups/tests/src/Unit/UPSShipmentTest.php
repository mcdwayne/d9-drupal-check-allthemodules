<?php

namespace Drupal\Tests\commerce_ups\Unit;

use Drupal\commerce_ups\UPSShipment;

/**
 * Class UPSShipmentTest.
 *
 * @coversDefaultClass \Drupal\commerce_ups\UPSShipment
 * @group commerce_ups
 */
class UPSShipmentTest extends UPSUnitTestBase {

  /**
   * The UPS shipment object.
   *
   * @var \Drupal\commerce_ups\UPSShipment
   */
  protected $upsShipment;

  /**
   * Set up requirements for test.
   */
  public function setUp() {
    parent::setUp();

    $this->upsShipment = new UPSShipment();
  }

  /**
   * Test ship from address.
   *
   * @covers ::setShipFrom
   */
  public function testShipFrom() {
    $api_shipment = $this->upsShipment->getShipment($this->mockShipment(), $this->mockShippingMethod());
    $ship_from = $api_shipment->getShipFrom()->getAddress();

    $this->assertEquals('1025 Brevard Rd', $ship_from->getAddressLine1());
    $this->assertEquals('Asheville', $ship_from->getCity());
    $this->assertEquals('NC', $ship_from->getStateProvinceCode());
    $this->assertEquals('28806', $ship_from->getPostalCode());
    $this->assertEquals('US', $ship_from->getCountryCode());
  }

  /**
   * Test ship to address.
   *
   * @covers ::setShipTo
   */
  public function testShipTo() {
    $api_shipment = $this->upsShipment->getShipment($this->mockShipment(), $this->mockShippingMethod());
    $ship_to = $api_shipment->getShipTo()->getAddress();

    $this->assertEquals('1025 Brevard Rd', $ship_to->getAddressLine1());
    $this->assertEquals('Asheville', $ship_to->getCity());
    $this->assertEquals('NC', $ship_to->getStateProvinceCode());
    $this->assertEquals('28806', $ship_to->getPostalCode());
    $this->assertEquals('US', $ship_to->getCountryCode());
  }

  /**
   * Test set package.
   *
   * @covers ::setPackage
   * @covers ::setDimensions
   * @covers ::setWeight
   * @covers ::setPackagingType
   */
  public function testPackage() {
    $api_shipment = $this->upsShipment->getShipment($this->mockShipment(), $this->mockShippingMethod());
    $packages = $api_shipment->getPackages();
    $this->assertCount(1, $packages);

    /** @var \Ups\Entity\Package $package */
    $package = reset($packages);

    $this->assertEquals(10, $package->getDimensions()->getLength());
    $this->assertEquals(3, $package->getDimensions()->getWidth());
    $this->assertEquals(10, $package->getDimensions()->getHeight());
    $this->assertEquals(10, $package->getPackageWeight()->getWeight());
    $this->assertEquals('00', $package->getPackagingType()->getCode());
  }

}
