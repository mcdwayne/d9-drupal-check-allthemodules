<?php

namespace Drupal\Tests\wunderstatus\Unit;

use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\wunderstatus\WunderstatusInfoCollector;

/**
 * @group wunderstatus
 */
class WunderstatusInfoCollectorTest extends UnitTestCase {

  const DATABASE_SYSTEM_VERSION = 'Database system version';
  const MODULE_VERSION = '8.x-1.0';

  /** @var ModuleHandlerInterface */
  private $moduleHandler;

  /** @var Extension[] */
  private $moduleList;

  /** @var WunderstatusInfoCollector */
  private $wunderstatusInfoCollector;

  public function setUp() {
    parent::setUp();

    $this->moduleList = [
      $this->getCoreExtension('module1'),
      $this->getCoreExtension('module2'),
      $this->getContribExtension('module3'),
      $this->getContribExtension('module4')
    ];

    $this->moduleHandler = $this->prophesize(ModuleHandlerInterface::class);
    $this->moduleHandler->getModuleList()->willReturn($this->moduleList);

    $this->wunderstatusInfoCollector = new WunderstatusInfoCollectorTestDouble($this->moduleHandler->reveal());
  }

  /**
   * @test
   */
  public function getVersionInfoShouldReturnVersionInfoForDrupalAndPhpAndDatabaseAndContribModules() {
    $versionInfo = $this->wunderstatusInfoCollector->getVersionInfo();

    $expectedInfo = [
      'Drupal ' . \Drupal::VERSION,
      'PHP ' . phpversion(),
      self::DATABASE_SYSTEM_VERSION,
      'module3 ' . self::MODULE_VERSION,
      'module4 ' . self::MODULE_VERSION
    ];

    $this->assertEquals($expectedInfo, $versionInfo);
  }

  private function getCoreExtension($name) {
    return $this->getExtension($name, 'core');
  }

  private function getContribExtension($name) {
    return $this->getExtension($name, 'contrib');
  }

  private function getExtension($name, $type) {
    return new Extension('', 'module', "$type/modules/$name/$name.info.yml");
  }
}

class WunderstatusInfoCollectorTestDouble extends WunderstatusInfoCollector {

  protected function getDatabaseSystemVersion() {
    return WunderstatusInfoCollectorTest::DATABASE_SYSTEM_VERSION;
  }

  protected function getInfoFile(Extension $module) {
    $version = WunderstatusInfoCollectorTest::MODULE_VERSION;

    return ["version: '$version'"];
  }

  protected function t($string, array $args = array(), array $options = array()) {
    return $string;
  }
}