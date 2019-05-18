<?php

namespace Drupal\Tests\nimbus\Unit\config;

use Drupal\nimbus\config\ConfigPathWithPermission;
use Drupal\Tests\UnitTestCase;

/**
 * Class ConfigPathWithPermissionTest.
 *
 * @package Drupal\Tests\nimbus\Unit\config
 */
class ConfigPathWithPermissionTest extends UnitTestCase {

  /**
   * Test the read permission method.
   */
  public function testHasReadPermissionTest() {
    $cpwp = new ConfigPathWithPermission('test', TRUE, TRUE, TRUE);
    $return = $cpwp->hasReadPermission('test.config');
    $this->assertEquals(TRUE, $return);
    $cpwp = new ConfigPathWithPermission('test', TRUE, TRUE, FALSE);
    $return = $cpwp->hasReadPermission('test.config');
    $this->assertEquals(TRUE, $return);
    $cpwp = new ConfigPathWithPermission('test', TRUE, FALSE, TRUE);
    $return = $cpwp->hasReadPermission('test.config');
    $this->assertEquals(TRUE, $return);
    $cpwp = new ConfigPathWithPermission('test', TRUE, FALSE, FALSE);
    $return = $cpwp->hasReadPermission('test.config');
    $this->assertEquals(TRUE, $return);
    $cpwp = new ConfigPathWithPermission('test', FALSE, TRUE, TRUE);
    $return = $cpwp->hasReadPermission('test.config');
    $this->assertEquals(FALSE, $return);
    $cpwp = new ConfigPathWithPermission('test', FALSE, TRUE, FALSE);
    $return = $cpwp->hasReadPermission('test.config');
    $this->assertEquals(FALSE, $return);
    $cpwp = new ConfigPathWithPermission('test', FALSE, FALSE, TRUE);
    $return = $cpwp->hasReadPermission('test.config');
    $this->assertEquals(FALSE, $return);
    $cpwp = new ConfigPathWithPermission('test', FALSE, FALSE, FALSE);
    $return = $cpwp->hasReadPermission('test.config');
    $this->assertEquals(FALSE, $return);
    $cpwp = new ConfigPathWithPermission('test');
    $return = $cpwp->hasReadPermission('test.config');
    $this->assertEquals(FALSE, $return);

    $cpwp = new ConfigPathWithPermission('test', function ($name) {
      return TRUE;
    });
    $return = $cpwp->hasReadPermission('test.config');
    $this->assertEquals(TRUE, $return);
  }

  /**
   * Test the write permission method.
   */
  public function testHasWritePermissionTest() {
    $data = ['module' => 'test'];

    $cpwp = new ConfigPathWithPermission('test', TRUE, TRUE, TRUE);
    $return = $cpwp->hasWritePermission('test.config', $data);
    $this->assertEquals(TRUE, $return);
    $cpwp = new ConfigPathWithPermission('test', TRUE, TRUE, FALSE);
    $return = $cpwp->hasWritePermission('test.config', $data);
    $this->assertEquals(TRUE, $return);
    $cpwp = new ConfigPathWithPermission('test', TRUE, FALSE, TRUE);
    $return = $cpwp->hasWritePermission('test.config', $data);
    $this->assertEquals(FALSE, $return);
    $cpwp = new ConfigPathWithPermission('test', TRUE, FALSE, FALSE);
    $return = $cpwp->hasWritePermission('test.config', $data);
    $this->assertEquals(FALSE, $return);
    $cpwp = new ConfigPathWithPermission('test', FALSE, TRUE, TRUE);
    $return = $cpwp->hasWritePermission('test.config', $data);
    $this->assertEquals(TRUE, $return);
    $cpwp = new ConfigPathWithPermission('test', FALSE, TRUE, FALSE);
    $return = $cpwp->hasWritePermission('test.config', $data);
    $this->assertEquals(TRUE, $return);
    $cpwp = new ConfigPathWithPermission('test', FALSE, FALSE, TRUE);
    $return = $cpwp->hasWritePermission('test.config', $data);
    $this->assertEquals(FALSE, $return);
    $cpwp = new ConfigPathWithPermission('test', FALSE, FALSE, FALSE);
    $return = $cpwp->hasWritePermission('test.config', $data);
    $this->assertEquals(FALSE, $return);
    $cpwp = new ConfigPathWithPermission('test');
    $return = $cpwp->hasWritePermission('test.config', $data);
    $this->assertEquals(FALSE, $return);
  }

  /**
   * Test the delete permission method.
   */
  public function testHasDeletePermissionTest() {
    $cpwp = new ConfigPathWithPermission('test', TRUE, TRUE, TRUE);
    $return = $cpwp->hasDeletePermission('test.config');
    $this->assertEquals(TRUE, $return);
    $cpwp = new ConfigPathWithPermission('test', TRUE, TRUE, FALSE);
    $return = $cpwp->hasDeletePermission('test.config');
    $this->assertEquals(FALSE, $return);
    $cpwp = new ConfigPathWithPermission('test', TRUE, FALSE, TRUE);
    $return = $cpwp->hasDeletePermission('test.config');
    $this->assertEquals(TRUE, $return);
    $cpwp = new ConfigPathWithPermission('test', TRUE, FALSE, FALSE);
    $return = $cpwp->hasDeletePermission('test.config');
    $this->assertEquals(FALSE, $return);
    $cpwp = new ConfigPathWithPermission('test', FALSE, TRUE, TRUE);
    $return = $cpwp->hasDeletePermission('test.config');
    $this->assertEquals(TRUE, $return);
    $cpwp = new ConfigPathWithPermission('test', FALSE, TRUE, FALSE);
    $return = $cpwp->hasDeletePermission('test.config');
    $this->assertEquals(FALSE, $return);
    $cpwp = new ConfigPathWithPermission('test', FALSE, FALSE, TRUE);
    $return = $cpwp->hasDeletePermission('test.config');
    $this->assertEquals(TRUE, $return);
    $cpwp = new ConfigPathWithPermission('test', FALSE, FALSE, FALSE);
    $return = $cpwp->hasDeletePermission('test.config');
    $this->assertEquals(FALSE, $return);
    $cpwp = new ConfigPathWithPermission('test');
    $return = $cpwp->hasDeletePermission('test.config');
    $this->assertEquals(FALSE, $return);
  }

}
