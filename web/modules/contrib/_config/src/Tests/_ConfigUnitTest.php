<?php

/**
 * @file
 * Definition of Drupal\_config\Tests\_ConfigUnitTest.
 */

namespace Drupal\_config\Tests;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests _Config.
 *
 * @group _Config
 */
class _ConfigUnitTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', '_config', '_config_test'];

  /**
   * Tests _Config::get().
   */
  public function testGet() {
    $tests = [];
    // Check simple value.
    $tests[] = ['_config_test', 'simple', 'value'];
    // Check dot delimited key.
    $tests[] = ['_config_test', 'dot.delimited', 'value'];
    // Check missing key.
    $tests[] = ['_config_test', 'missing', NULL];
    // Check nested array.
    $tests[] = ['_config_test', 'nested.array', 'value'];
    // Check get all.
    $tests[] = ['_config_test', NULL, [
        'simple' => 'value',
        'dot.delimited' => 'value',
        'nested' => ['array' => 'value'],
      ],
    ];
    foreach ($tests as $test) {
      $name = $test[0];
      $key = $test[1];
      $expected = $test[2];
      $result = _config($name, $key);
      $this->assertEquals($expected, $result);
    }

    // Check config exists.
    $this->assertEquals(_config_exists('missing'), FALSE);
  }

}
