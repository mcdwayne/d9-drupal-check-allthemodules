<?php

namespace Drupal\config_override\Tests\Unit;

use Drupal\config_override\ConfigOverrideServiceProvider;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamWrapper;

/**
 * @coversDefaultClass \Drupal\config_override\ConfigOverrideServiceProvider
 * @group config_override
 */
class ConfigOverrideServiceProviderTest extends \PHPUnit_Framework_TestCase {

  /**
   * @covers ::alter
   */
  public function testNotExistingEnvironmentFile() {
    vfsStreamWrapper::register();
    new vfsStreamDirectory('drupal');

    $sut = new ConfigOverrideServiceProvider();

    $container = new ContainerBuilder();
    $container->set('app.root', 'vfs://');
    $sut->alter($container);

    $this->assertEquals([],
      $container->getParameter('config_override__environment'));
  }

  /**
   * @covers ::alter
   */
  public function testWithEnvironmentFileWithDots() {
    $vfs_root = vfsStream::setup('drupal_root');
    vfsStream::create([
      'sites/default/.env' => '
CONFIG___CONFIG__NAME___KEY__WITH_DOT=value
',
    ]);

    $sut = new ConfigOverrideServiceProvider();

    $container = new ContainerBuilder();
    $container->set('app.root', $vfs_root->url());
    $sut->alter($container);

    $this->assertEquals([
      'config.name' => [
        'key.with_dot' => 'value',
      ],
    ], $container->getParameter('config_override__environment'));
  }

  /**
   * @covers ::alter
   */
  public function testWithEnvironmentFile() {
    $vfs_root = vfsStream::setup('drupal_root');
    vfsStream::create([
      'sites/default/.env' => '
CONFIG___NAME___KEY=value
CONFIG___NAME___KEY2=value2
CONFIG___NAME2___KEY2=value2
CONFIG___NAME3___KEY3=value3
',
    ]);

    $sut = new ConfigOverrideServiceProvider();

    $container = new ContainerBuilder();
    $container->set('app.root', $vfs_root->url());
    $sut->alter($container);

    $this->assertEquals([
      'name' => [
        'key' => 'value',
        'key2' => 'value2',
      ],
      'name2' => [
        'key2' => 'value2',
      ],
      'name3' => [
        'key3' => 'value3',
      ],
    ], $container->getParameter('config_override__environment'));
  }

  /**
   * @covers ::alter
   */
  public function testWithEnvironmentFiles() {
    $vfs_root = vfsStream::setup('drupal_root');
    vfsStream::create([
      'sites/default/.environment' => '
CONFIG___NAME___KEY2=value2
CONFIG___NAME3___KEY3=value3
',
      'sites/default/.env' => '
CONFIG___NAME___KEY=value
CONFIG___NAME2___KEY2=value2
',
    ]);

    $sut = new ConfigOverrideServiceProvider();

    $container = new ContainerBuilder();
    $container->set('app.root', $vfs_root->url());
    $sut->alter($container);

    $this->assertEquals([
      'name' => [
        'key' => 'value',
        'key2' => 'value2',
      ],
      'name2' => [
        'key2' => 'value2',
      ],
      'name3' => [
        'key3' => 'value3',
      ],
    ], $container->getParameter('config_override__environment'));
  }

  /**
   * @covers ::alter
   */
  public function testWithEnvironmentFilesAndOverrideInSitesDefault() {
    $vfs_root = vfsStream::setup('drupal_root');
    vfsStream::create([
      'sites/default/.env' => '
CONFIG___NAME___KEY=value
CONFIG___NAME___KEY=override_value
',
      'sites/default/.environment' => '
CONFIG___NAME___KEY2=value2
CONFIG___NAME3___KEY3=value3
',
    ]);

    $sut = new ConfigOverrideServiceProvider();

    $container = new ContainerBuilder();
    $container->set('app.root', $vfs_root->url());
    $sut->alter($container);

    $this->assertEquals([
      'name' => [
        'key' => 'override_value',
        'key2' => 'value2',
      ],
      'name3' => [
        'key3' => 'value3',
      ],
    ], $container->getParameter('config_override__environment'));
  }

}
