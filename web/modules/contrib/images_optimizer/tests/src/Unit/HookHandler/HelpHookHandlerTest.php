<?php

namespace Drupal\Tests\images_optimizer\Unit\HookHandler;

use Drupal\images_optimizer\HookHandler\HelpHookHandler;
use Drupal\Tests\UnitTestCase;

/**
 * Unit test class for the HelpHookHandler class.
 *
 * @package Drupal\Tests\images_optimizer\Unit\HookHandler
 */
class HelpHookHandlerTest extends UnitTestCase {

  /**
   * The help hook handler to test.
   *
   * @var \Drupal\images_optimizer\HookHandler\HelpHookHandler
   */
  private $helpHookHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->helpHookHandler = new HelpHookHandler();
  }

  /**
   * Test process() with an unrelated route name.
   */
  public function testProcessWithAnUnrelatedRouteName() {
    $this->assertNull($this->helpHookHandler->process('foo'));
  }

}
