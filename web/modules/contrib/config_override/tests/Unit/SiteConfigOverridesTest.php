<?php

namespace Drupal\config_override\Tests\Unit {

  use Drupal\Component\Serialization\Yaml;
  use Drupal\config_override\SiteConfigOverrides;
  use Drupal\Core\Cache\CacheBackendInterface;
  use org\bovigo\vfs\vfsStream;

  /**
   * @coversDefaultClass \Drupal\config_override\SiteConfigOverrides
   * @group config_override
   */
  class SiteConfigOverridesTest extends \PHPUnit_Framework_TestCase {

    public function testSiteOverride() {
      $cache_backend = $this->prophesize(CacheBackendInterface::class);
      $site_override = new SiteConfigOverrides('vfs://drupal', $cache_backend->reveal());

      vfsStream::setup('drupal');
      vfsStream::create([
        'sites' => [
          'default' => [
            'config' => [
              'override' => [
                'system.site.yml' => Yaml::encode(['name' => 'Hey jude']),
              ],
            ],
          ],
        ],
      ]);

      $result = $site_override->loadOverrides(['system.site']);
      $this->assertEquals([
        'system.site' => [
          'name' => 'Hey jude',
        ],
      ], $result);
    }
  }

}

namespace Drupal\config_override {

  function config_get_config_directory() {
    return 'sites/default/config/override';
  }

}
