<?php

namespace Drupal\Tests\chunker\Kernel;

use Drupal\KernelTests\KernelTestBase;


/**
 * Tests running the filter directly on some given strings (no fields).
 *
 * @coversDefaultClass \Drupal\chunker\Controller\ExampleController
 *
 * @requires chunker
 *
 * @group Chunker
 */
class ChunkerFilterTest extends KernelTestBase {

  /**
   * The entity to use when building test content.
   *
   * @var string
   */
  private $testBundle = 'page';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'field_ui',
    'chunker',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

  }

  /**
   * Test that tests run at all.
   */
  public function testImAlive() {
    $this->assert(TRUE);
  }

}
