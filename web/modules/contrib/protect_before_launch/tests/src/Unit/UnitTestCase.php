<?php

namespace Drupal\Tests\protect_before_launch\Unit;

use Drupal\Tests\UnitTestCase as BaseUnitTestCase;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Unit test helpers.
 */
class UnitTestCase extends BaseUnitTestCase {
  use PhpunitCompatibilityTrait;

  /**
   * Verify the setInput() support is available when testing console commands.
   */
  public function verifySymfonyConsoleInputsSupport() {
    if (version_compare(Kernel::VERSION, '3.2', '<')) {
      $this->markTestSkipped('Requires Symfony 3.2');
    }
  }

}
