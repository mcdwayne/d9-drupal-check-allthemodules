<?php

namespace Drupal\Tests\adva\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Provides basic tests for the Adva module.
 *
 * Test Roadmap - I plan to implement the following test coverage.
 * - Consumer Plugin manager service
 * - Producer Plugin manager service
 * - Consumer plugin base tests
 *   - Basic consumer config
 *   - Overriding Consumer
 * - Access Provider base tests
 *   - Entity Type Access Provide
 *   - Reference Access Provider
 * - Anon Access provider plugin tests
 * - Permission Generation?
 * - Param converters?
 * - Hooks?
 * - Batch job?
 *
 * Sub module test (in those modules)
 * - Node access module tests (hook_node access)
 * - Media test
 *
 * @group adva
 */
class AdvaTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['adva'];

  /**
   * Tests conditions.
   */
  public function testAdva() {

  }

}
