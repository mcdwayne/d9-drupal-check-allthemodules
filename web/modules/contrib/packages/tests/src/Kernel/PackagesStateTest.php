<?php

namespace Drupal\Tests\packages\Kernel;

use Drupal\Tests\packages\Kernel\PackagesTestBase;

/**
 * Tests Package states.
 *
 * @group packages
 */
class PackagesStateTest extends PackagesTestBase {

  /**
   * Tests package state active status.
   */
  public function testStateActive() {
    // Get the state of the test package plugin.
    $state = $this->packages->getState('test');

    // A state should only be active if it is both enabled and accessible.
    $state->disable();
    $state->setAccess(FALSE);
    $this->assertFalse($state->isActive());
    $state->enable();
    $state->setAccess(FALSE);
    $this->assertFalse($state->isActive());
    $state->disable();
    $state->setAccess(TRUE);
    $this->assertFalse($state->isActive());
    $state->enable();
    $state->setAccess(TRUE);
    $this->assertTrue($state->isActive());
  }

}
