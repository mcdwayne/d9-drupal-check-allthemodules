<?php

namespace Drupal\Tests\sourcepoint\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\sourcepoint\Cmp;
use Drupal\Core\Config\ConfigBase;

/**
 * @coversDefaultClass \Drupal\sourcepoint\Cmp
 *
 * @group sourcepoint
 */
class CmpTest extends UnitTestCase {

  /**
   * Get mocked config factory.
   *
   * @param \Drupal\Core\Config\ConfigBase $config
   *   Mocked config class.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject
   *   Mocked config factory.
   */
  protected function getConfigFactory(ConfigBase $config) {
    $config_factory = $this->getMockForAbstractClass('\Drupal\Core\Config\ConfigFactoryInterface');
    $config_factory->method('get')->willReturn($config);
    return $config_factory;
  }

  /**
   * Get mocked config with provided value map.
   *
   * @param array $value_map
   *   Value map for getter.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject
   *   Mocked config class.
   */
  protected function getConfig(array $value_map) {
    $config = $this->getMockBuilder('Drupal\Core\Config\ConfigBase')
      ->setMethods(['get'])
      ->getMockForAbstractClass();
    $config->method('get')->will(
      $this->returnValueMap($value_map)
    );
    return $config;
  }

  /**
   * @covers ::enabled
   */
  public function testEnabled() {
    $config = $this->getConfig([['cmp_enabled', '1']]);
    $config_factory = $this->getConfigFactory($config);
    $cmp = new Cmp($config_factory);
    $this->assertEquals(TRUE, $cmp->enabled());
  }

  /**
   * @covers ::getUrl
   */
  public function testGetUrl() {
    $config = $this->getConfig([
      ['cmp_enabled', '1'],
      ['cmp_privacy_manager_id', 123],
      ['cmp_site_id', 456],
      ['mms_domain', 'mms.example.com'],
    ]);
    $config_factory = $this->getConfigFactory($config);
    $cmp = new Cmp($config_factory);

    // Check URI.
    $url = $cmp->getUrl();
    $this->assertEquals($url->getUri(), '//mms.example.com/cmp/privacy_manager');

    // Check query parameters.
    $query = $url->getOption('query');
    $this->assertEquals($query['privacy_manager_id'], 123);
    $this->assertEquals($query['site_id'], 456);
  }

  /**
   * @covers ::getOverlay
   */
  public function testGetOverlay() {
    $config = $this->getConfig([
      ['cmp_enabled', '1'],
      ['cmp_privacy_manager_id', 123],
      ['cmp_site_id', 456],
      ['mms_domain', 'mms.example.com'],
      ['cmp_overlay_height', '600px'],
      ['cmp_overlay_width', '500px'],
    ]);
    $config_factory = $this->getConfigFactory($config);
    $cmp = new Cmp($config_factory);

    // Check overlay render array.
    $overlay = $cmp->getOverlay();
    $this->assertEquals('sourcepoint_cmp_overlay', $overlay['#theme']);
    $this->assertEquals($cmp->getUrl(), $overlay['#url']);
    $this->assertEquals('600px', $overlay['#height']);
    $this->assertEquals('500px', $overlay['#width']);
    $this->assertEquals('sourcepoint/cmp', $overlay['#attached']['library'][0]);
  }

  /**
   * @covers ::getShimUrl
   */
  public function testGetShimUrl() {
    $config = $this->getConfig([
      ['cmp_shim_url', '//shim.example.com/shim.js'],
    ]);
    $config_factory = $this->getConfigFactory($config);
    $cmp = new Cmp($config_factory);

    $this->assertEquals($cmp->getShimUrl(), '//shim.example.com/shim.js');
  }

}
