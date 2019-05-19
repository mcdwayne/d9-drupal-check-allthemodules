<?php

namespace Drupal\Tests\tmgmt_smartling\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\tmgmt_smartling\Smartling\ConnectorInfo;

/**
 * Test ConnectorInfo class methods.
 *
 * @group tmgmt_smartling
 */
class ConnectorInfoTest extends KernelTestBase {

  public static $modules = ['system'];

  public function testGetLibName() {
    $this->assertEquals(ConnectorInfo::getLibName(), 'drupal-tmgmt-connector');
  }

  public function testGetLibVersion() {
    $this->assertTrue(preg_match('/(^\d\.\d\.\d$)|(^\d\.\d\.\d-rc\d$)/', ConnectorInfo::getLibVersion('system')) === 1);
  }

  public function testGetDependenciesVersionsAsString() {
    $this->assertTrue(preg_match('/^tmgmt_extension_suit\/(\d+\.x-\d+\.\d+|\d+\.x-\d+\.x-dev|unknown) tmgmt\/(\d+\.x-\d+\.\d+|\d+\.x-\d+\.x-dev|unknown)$/', ConnectorInfo::getDependenciesVersionsAsString()) === 1);
  }
}
