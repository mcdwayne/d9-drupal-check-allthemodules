<?php

namespace Drupal\Tests\geoip\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\geoip\Plugin\GeoLocator\Cdn;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests the CDN locator.
 *
 * @coversDefaultClass \Drupal\geoip\Plugin\GeoLocator\Cdn
 *
 * @group GeoIP
 */
class CdnGeoLocatorTest extends UnitTestCase {

  /**
   * Test the geolocate method for Cdn plugin.
   *
   * @covers ::geolocate
   * @backupGlobals disabled
   */
  public function testGeolocate() {
    $config = $this->prophesize(ImmutableConfig::class);
    $config->get('debug')->willReturn(FALSE);
    $config_factory = $this->prophesize(ConfigFactoryInterface::class);
    $config_factory->get('geoip.geolocation')->willReturn($config->reveal());
    $logger = $this->prophesize(LoggerInterface::class);

    $locator = new Cdn([], 'cdn', [
      'label' => 'CDN',
      'description' => 'Checks for geolocation headers sent by CDN services',
      'weight' => 10,
    ], $config_factory->reveal(), $logger->reveal());

    $this->assertEquals(NULL, $locator->geolocate('127.0.0.1'));

    $_SERVER['HTTP_CF_IPCOUNTRY'] = 'US';
    $this->assertEquals('US', $locator->geolocate('127.0.0.1'));

    $_SERVER['HTTP_CLOUDFRONT_VIEWER_COUNTRY'] = 'CA';
    // We can't equal CA since we manually check Cloudflare first.
    $this->assertNotEquals('CA', $locator->geolocate('127.0.0.1'));
    unset($_SERVER['HTTP_CF_IPCOUNTRY']);
    $this->assertEquals('CA', $locator->geolocate('127.0.0.1'));

    unset($_SERVER['HTTP_CLOUDFRONT_VIEWER_COUNTRY']);
    $_SERVER['HTTP_MY_CUSTOM_HEADER'] = 'FR';
    // @todo this needs to be updated when custom header implemented.
    $this->assertEquals(NULL, $locator->geolocate('127.0.0.1'));
  }

}
