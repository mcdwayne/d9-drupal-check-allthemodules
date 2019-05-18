<?php

namespace Drupal\Tests\automatic_updates\Kernel\ReadinessChecker;

use Drupal\automatic_updates\ReadinessChecker\DiskSpace;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests disk space readiness checking.
 *
 * @group automatic_updates
 */
class DiskSpaceTest extends KernelTestBase {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'automatic_updates',
  ];

  /**
   * Tests the functionality of disk space readiness checks.
   */
  public function testDiskSpace() {
    // No disk space issues.
    $disk_space = new DiskSpace($this->container->get('logger.channel.automatic_updates'), $this->container->get('automatic_updates.drupal_finder'));
    $messages = $disk_space->run();
    $this->assertEmpty($messages);

    // Out of space.
    $disk_space = new TestDiskSpace($this->container->get('logger.channel.automatic_updates'), $this->container->get('automatic_updates.drupal_finder'));
    $messages = $disk_space->run();
    $this->assertCount(1, $messages);

    // Out of space not the same logical disk.
    $disk_space = new TestDiskSpaceNonSameDisk($this->container->get('logger.channel.automatic_updates'), $this->container->get('automatic_updates.drupal_finder'));
    $messages = $disk_space->run();
    $this->assertCount(2, $messages);
  }

}

/**
 * Class TestDiskSpace.
 */
class TestDiskSpace extends DiskSpace {

  /**
   * Override the default free disk space minimum to an insanely high number.
   */
  const MINIMUM_DISK_SPACE = 99999999999999999999999999999999999999999999999999;

}

/**
 * Class TestDiskSpaceNonSameDisk.
 */
class TestDiskSpaceNonSameDisk extends TestDiskSpace {

  /**
   * {@inheritdoc}
   */
  protected function areSameLogicalDisk($root, $vendor) {
    return FALSE;
  }

}
