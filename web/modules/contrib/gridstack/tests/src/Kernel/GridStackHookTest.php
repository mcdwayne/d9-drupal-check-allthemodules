<?php

namespace Drupal\Tests\gridstack\Kernel;

use Drupal\Tests\blazy\Kernel\BlazyKernelTestBase;
use Drupal\Tests\gridstack\Traits\GridStackUnitTestTrait;

/**
 * Tests the GridStack manager methods.
 *
 * @coversDefaultClass \Drupal\gridstack\GridStackHook
 *
 * @group gridstack
 */
class GridStackHookTest extends BlazyKernelTestBase {

  use GridStackUnitTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'field',
    'file',
    'filter',
    'image',
    'media',
    'blazy',
    'gridstack',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->gridstackHook = $this->container->get('gridstack.hook');
  }

  /**
   * Tests cases for various methods.
   *
   * @covers ::libraryInfoBuild
   */
  public function testGridStackHookMethods() {
    $hook = $this->gridstackHook;

    // Verify libraries.
    $libraries = $hook->libraryInfoBuild();
    $this->assertArrayHasKey('gridstack.default', $libraries);
  }

}
