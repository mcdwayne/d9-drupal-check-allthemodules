<?php

namespace Drupal\Tests\nimbus\Unit\config;

use Drupal\nimbus\config\ConfigPath;
use Drupal\Tests\UnitTestCase;

/**
 * Class ConfigPath.
 *
 * @package Drupal\nimbus\config
 */
class ConfigPathTest extends UnitTestCase {

  /**
   * Test the read permission method.
   */
  public function testHasReadPermission() {
    $cpwp = new ConfigPath('test_path');
    $return = $cpwp->hasReadPermission('test.config');
    $this->assertEquals(TRUE, $return);
  }

  /**
   * Test the write permission method.
   */
  public function testHasWritePermission() {
    $data = ['module' => 42];
    $cpwp = new ConfigPath('test_path');
    $return = $cpwp->hasWritePermission('test.config', $data);
    $this->assertEquals(TRUE, $return);
  }

  /**
   * Test the delete permission method.
   */
  public function testHasDeletePermission() {
    $cpwp = new ConfigPath('test_path');
    $return = $cpwp->hasDeletePermission('test.config');
    $this->assertEquals(TRUE, $return);
  }

}
