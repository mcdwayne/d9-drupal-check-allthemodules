<?php

namespace Drupal\Tests\commerce_usps\Unit;

use Drupal\commerce_shipping\ShippingRate;
use Drupal\commerce_usps\USPSShipment;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Drupal\commerce_usps\USPSRateRequest;

/**
 * Class USPSRateRequestTest.
 *
 * @coversDefaultClass \Drupal\commerce_usps\USPSRateRequest
 * @group commerce_usps
 */
class USPSRateRequestTest extends USPSUnitTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Add the services to the config.
    $this->setConfig(['services' => [1, 2, 3, 4, 6, 7]]);

    // Mock all the objects and set the config.
    $event_dispatcher = new EventDispatcher();
    $this->uspsShipment = new USPSShipment($event_dispatcher);
    $this->rateRequest = new USPSRateRequest($this->uspsShipment, $event_dispatcher);
    $this->rateRequest->setConfig($this->getConfig());
  }

  /**
   * Tests getRates().
   *
   * @covers ::getRates
   * @covers ::buildRate
   * @covers ::setMode
   * @covers ::setShipment
   * @covers ::resolveRates
   */
  public function testGetRates() {
    $config = $this->getConfig();
    $shipment = $this->mockShipment();

    // Fetch rates from the USPS api.
    $rates = $this->rateRequest->getRates($shipment);

    // Make sure the same number of rates requested
    // is returned.
    $this->assertEquals(count($config['services']), count($rates));

    /** @var \Drupal\commerce_shipping\ShippingRate $rate */
    foreach ($rates as $rate) {
      $this->assertInstanceOf(ShippingRate::class, $rate);
      $this->assertNotEmpty($rate->getAmount()->getNumber());
    }
  }

  /**
   * Test cleaning service names.
   *
   * @covers ::cleanServiceName
   */
  public function testCleanServiceName() {
    $service = 'Priority Mail Express 2-Day&lt;sup&gt;&#8482;&lt;/sup&gt;';
    $cleaned = $this->rateRequest->cleanServiceName($service);
    $this->assertEquals('Priority Mail Express 2-Day', $cleaned);
  }

  /**
   * Test package setup.
   *
   * @covers ::getPackages
   */
  public function testGetPackages() {
    $this->rateRequest->setShipment($this->mockShipment());
    $packages = $this->rateRequest->getPackages();
    // TODO: Support multiple packages.
    /** @var \USPS\RatePackage $package */
    $package = reset($packages);
    $info = $package->getPackageInfo();
    $this->assertEquals(28806, $info['ZipOrigination']);
    $this->assertEquals(80465, $info['ZipDestination']);
    $this->assertEquals('ALL', $info['Service']);
    $this->assertEquals(10, $info['Pounds']);
    $this->assertEquals(0, $info['Ounces']);
    $this->assertEquals('VARIABLE', $info['Container']);
    $this->assertEquals('REGULAR', $info['Size']);
    $this->assertEquals(3, $info['Width']);
    $this->assertEquals(10, $info['Length']);
    $this->assertEquals(10, $info['Height']);
    $this->assertEquals(0, $info['Girth']);
    $this->assertEquals(TRUE, $info['Machinable']);
    $this->assertEquals(date('Y-m-d'), $info['ShipDate']);
  }

}
