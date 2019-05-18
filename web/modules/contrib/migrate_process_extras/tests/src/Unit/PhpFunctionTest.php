<?php

namespace Drupal\Tests\migrate_process_extras\Unit;

use Drupal\migrate_process_extras\Plugin\migrate\process\PhpFunction;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * Test the php_function process plugin.
 *
 * @group migrate_process_extras
 */
class PhpFunctionTest extends MigrateProcessTestCase {

  /**
   * Test the php function is applied.
   */
  public function testTransform() {
    $plugin = new PhpFunction(['function' => 'sprintf'], 'php_function', []);
    // Test with multiple arguments.
    $this->assertEquals("Hello To The World", $plugin->transform([
      'Hello %s %s %s',
      'To',
      'The',
      'World',
    ], $this->migrateExecutable, $this->row, 'destinationproperty'));

    // Test with one argument.
    $this->assertEquals('Hello', $plugin->transform('Hello', $this->migrateExecutable, $this->row, 'destinationproperty'));
  }

}
