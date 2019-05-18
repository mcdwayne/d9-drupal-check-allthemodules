<?php

namespace Drupal\Tests\automatic_updates\Kernel\ReadinessChecker;

use Drupal\automatic_updates\ReadinessChecker\ModifiedCode;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests modified code readiness checking.
 *
 * @group automatic_updates
 */
class ModifiedCodeTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'automatic_updates',
  ];

  /**
   * Tests the functionality of modified code readiness checks.
   */
  public function testModifiedCode() {
    // No modified code.
    $modified_code = new ModifiedCode($this->container->get('logger.channel.automatic_updates'), $this->container->get('automatic_updates.drupal_finder'));
    $messages = $modified_code->run();
    $this->assertEmpty($messages);
  }

}
