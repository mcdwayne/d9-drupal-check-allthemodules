<?php

namespace Drupal\Tests\geoip\Unit;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\geoip\GeoLocation;
use Drupal\Tests\UnitTestCase;
use Drupal\geoip\GeoLocatorManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\geoip\Plugin\GeoLocator\GeoLocatorInterface;

/**
 * Tests the geolocator plugin manager.
 *
 * @coversDefaultClass \Drupal\geoip\GeoLocation
 *
 * @group GeoIP
 */
class GeoLocationTest extends UnitTestCase {

  /**
   * Test getGeoLocatorId.
   *
   * @covers ::getGeoLocatorId
   */
  public function testGetGeoLocatorId() {
    $geolocators_manager = $this->prophesize(GeoLocatorManager::class);
    $config = $this->prophesize(ImmutableConfig::class);
    $config->get('debug')->willReturn(FALSE);
    $config->get('plugin_id')->willReturn('local');
    $config_factory = $this->prophesize(ConfigFactoryInterface::class);
    $config_factory->get('geoip.geolocation')->willReturn($config->reveal());
    $cache_backend = $this->prophesize(CacheBackendInterface::class);

    $geolocation = new GeoLocation($geolocators_manager->reveal(), $config_factory->reveal(), $cache_backend->reveal());

    $this->assertEquals('local', $geolocation->getGeoLocatorId());
  }

  /**
   * Test getGeoLocator.
   *
   * @covers ::getGeoLocator
   */
  public function testGetGeoLocator() {
    $geolocators_manager = $this->prophesize(GeoLocatorManager::class);
    $config = $this->prophesize(ImmutableConfig::class);
    $config->get('debug')->willReturn(FALSE);
    $config->get('plugin_id')->willReturn('local');
    $config_factory = $this->prophesize(ConfigFactoryInterface::class);
    $config_factory->get('geoip.geolocation')->willReturn($config->reveal());
    $cache_backend = $this->prophesize(CacheBackendInterface::class);
    $geolocators_manager->createInstance('local')->willReturn($this->prophesize(GeoLocatorInterface::class)->reveal());

    $geolocation = new GeoLocation($geolocators_manager->reveal(), $config_factory->reveal(), $cache_backend->reveal());
    $locator = $geolocation->getGeoLocator();

    $this->assertTrue($locator instanceof GeoLocatorInterface);
  }

  /**
   * Test getGeoLocator.
   *
   * @covers ::geolocate
   */
  public function testGeolocate() {
    $geolocators_manager = $this->prophesize(GeoLocatorManager::class);
    $config = $this->prophesize(ImmutableConfig::class);
    $config->get('debug')->willReturn(FALSE);
    $config->get('plugin_id')->willReturn('local');
    $config_factory = $this->prophesize(ConfigFactoryInterface::class);
    $config_factory->get('geoip.geolocation')->willReturn($config->reveal());
    $cache_backend = $this->prophesize(CacheBackendInterface::class);

    $locator = $this->prophesize(GeoLocatorInterface::class);
    $locator->geolocate('127.0.0.1')->willReturn(NULL);
    $locator->geolocate('2605:a000:140d:c18f:5995:dfe1:7914:4b4f')->willReturn('US');
    $locator->geolocate('23.86.161.12')->willReturn('CA');

    $geolocators_manager->createInstance('local')->willReturn($locator->reveal());

    $geolocation = new GeoLocation($geolocators_manager->reveal(), $config_factory->reveal(), $cache_backend->reveal());

    $this->assertNull($geolocation->geolocate('127.0.0.1'));
    $this->assertEquals($geolocation->geolocate('2605:a000:140d:c18f:5995:dfe1:7914:4b4f'), 'US');
    $this->assertEquals($geolocation->geolocate('23.86.161.12'), 'CA');
  }

}
