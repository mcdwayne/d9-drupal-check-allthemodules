<?php

namespace Drupal\config_override\Tests\Unit;

use Drupal\Component\Serialization\Yaml;
use Drupal\config_override\ModuleConfigOverrides;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleHandlerInterface;
use org\bovigo\vfs\vfsStream;

/**
 * @coversDefaultClass \Drupal\config_override\ModuleConfigOverrides
 * @group config_override
 */
class ModuleConfigOverridesTest extends \PHPUnit_Framework_TestCase {

  public function testModuleOverrides() {
    $cache_backend = $this->prophesize(CacheBackendInterface::class);
    $module_handler = $this->prophesize(ModuleHandlerInterface::class);

    $extension_a = new Extension('vfs://drupal', 'module', 'modules/module_a/module_a.info.yml');
    $extension_b = new Extension('vfs://drupal', 'module', 'modules/module_b/module_b.info.yml');

    vfsStream::setup('drupal');
    vfsStream::create([
      'modules' => [
        'module_a' => [
          'config' => [
            'override' => [
              'system.site.yml' => Yaml::encode([
                'name' => 'Hey jude',
              ]),
            ],
          ],
        ],
        'module_b' => [
          'config' => [
            'override' => [
              'system.site.yml' => Yaml::encode([
                'slogan' => 'Muh',
              ]),
            ],
          ],
        ],
      ],
    ]);

    $module_handler->getModuleList()->willReturn(['module_a' => $extension_a, 'module_b' => $extension_b]);
    $module_overrides = new ModuleConfigOverrides('vfs://drupal', $module_handler->reveal(), $cache_backend->reveal());

    $expected = [
      'system.site' => [
        'name' => 'Hey jude',
        'slogan' => 'Muh',
      ],
    ];
    $result = $module_overrides->loadOverrides(['system.site']);

    $this->assertEquals($expected, $result);
  }

}
