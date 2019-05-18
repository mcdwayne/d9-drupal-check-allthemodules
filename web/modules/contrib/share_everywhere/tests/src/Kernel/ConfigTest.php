<?php

namespace Drupal\Tests\share_everywhere\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Configuration tests.
 *
 * @group share_everywhere
 */
class ConfigTest extends KernelTestBase {

  protected static $modules = ['share_everywhere'];

  /**
   * Tests the configuration install.
   */
  public function testConfigInstall() {
    $this->installConfig('share_everywhere');
  }

}
