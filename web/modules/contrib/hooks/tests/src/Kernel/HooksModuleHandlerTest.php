<?php

/**
 * @file
 * Contains \Drupal\Tests\hooks\HooksModuleHandlerTest
 */

namespace Drupal\Tests\hooks\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Test our OO hooks.
 *
 * @group Hooks
 */
class HooksModuleHandlerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['hooks', 'hooks_test_hooks'];

  /**
   * Test the OO hooks.
   */
  public function testHooks() {
    // Test simple hook invoked.
    $test_data = 'test data';
    $context1 = '';
    $context2 = '';

    $this->moduleHandler()
      ->alter('test_hook', $test_data, $context1, $context2);
    $this->assertSame('test data Manipulated by onTestHook', $test_data);

    // Test the contexts are preserved when no changes are made.
    $context1 = 'context1';
    $context2 = 'context2';
    $this->moduleHandler()
      ->alter('test_hook_no_changes', $test_data, $context1, $context2);
    $this->assertSame('context1', $context1, 'Context 1 value was preserved');
    $this->assertSame('context2', $context2, 'Context 2 value was preserved');

    // Test with an array of types.
    $test_data = 'test data';
    $this->moduleHandler()
      ->alter(['test_hook', 'test_hook_multiple'], $test_data, $context1, $context2);
    $this->assertSame('ontesthook_context1', $context1, 'Context 1 new value was passed back');
    $this->assertSame('ontesthook_context2', $context2, 'Context 2 new value was passed back');

    $this->assertSame('test data Manipulated by onTestHook Manipulated by onTestHookMultiple', $test_data);
  }

  /**
   * Returns the ModuleHandler.
   *
   * @return \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected function moduleHandler() {
    return $this->container->get('module_handler');
  }

}
