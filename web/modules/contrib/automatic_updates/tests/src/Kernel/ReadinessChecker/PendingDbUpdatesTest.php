<?php

namespace Drupal\Tests\automatic_updates\Kernel\ReadinessChecker;

use Drupal\automatic_updates\ReadinessChecker\PendingDbUpdates;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests pending db updates readiness checking.
 *
 * @group automatic_updates
 */
class PendingDbUpdatesTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'automatic_updates',
  ];

  /**
   * Tests pending db updates readiness checks.
   */
  public function testPendingDbUpdates() {
    $messages = $this->container->get('automatic_updates.pending_db_updates')->run();
    $this->assertEmpty($messages);

    $messages = (new TestPendingDbUpdates())->run();
    $this->assertEquals('There are pending database updates, therefore updates cannot be applied. Please run update.php.', $messages[0]);
  }

}

/**
 * Class TestPendingDbUpdates.
 */
class TestPendingDbUpdates extends PendingDbUpdates {

  /**
   * {@inheritdoc}
   */
  protected function areDbUpdatesPending() {
    return TRUE;
  }

}
