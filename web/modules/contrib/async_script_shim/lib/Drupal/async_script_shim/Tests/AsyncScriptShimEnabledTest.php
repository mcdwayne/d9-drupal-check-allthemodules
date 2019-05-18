<?php

/**
 * @file
 * Tests for Async script shim module.
 */

namespace Drupal\async_script_shim\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test extended functionality of Async script shim module.
 */
class AsyncScriptShimEnabledTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('async_script_shim_test', 'async_script_shim');

  /**
   * Implements getInfo().
   */
  public static function getInfo() {
    return array(
      'name' => t('Async script shim tests with shim enabled.'),
      'description' => t('Test asyncs scripts with Async script shim module enabled.'),
      'group' => 'Async script shim',
    );
  }

  /**
   * Implements DrupalWebTestCase::setup().
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Check scripts with module enabled and scripts not aggregated.
   */
  public function testAsyncScriptShimEnabledNonAggregated() {
    config('system.performance')
      ->set('preprocess.js', FALSE)
      ->save();

    $this->drupalGet('');
    $this->assertNoPattern('/src=".*async_script_shim_test_async_1\.js/', 'async_1.js is <em>not</em> included via &lt;script&gt;.');
    $this->assertNoPattern('/src=".*async_script_shim_test_async_2\.js/', 'async_2.js is <em>not</em> included via &lt;script&gt;.');
    $this->assertPattern('/src=".*async_script_shim_test_sync_1\.js/', 'sync_1.js <em>is</em> included via &lt;script&gt;.');
    $this->assertRaw('s.async = true;', 'Some script(s) <em>are</em> inserted via shim.');
    $this->assertNoPattern('/src=".*js\/js_.*"/', '<em>No</em> aggregated script(s) are included.');
    $this->assertNoPattern('/s.src = .*js\/js_.*;/', '<em>No</em> aggregated script(s) <em>are</em> inserted via shim.');
    $this->assertNoRaw('async="async"', '<em>No</em> scripts use the async attribute.');
  }

  /**
   * Check scripts with module enabled and scripts aggregated.
   */
  public function testAsyncScriptShimEnabledAggregated() {
    config('system.performance')
      ->set('preprocess.js', TRUE)
      ->save();

    $this->drupalGet('');
    $this->assertNoPattern('/src=".*async_script_shim_test_async_1\.js/', 'async_1.js is <em>not</em> included via &lt;script&gt;.');
    $this->assertNoPattern('/src=".*async_script_shim_test_async_2\.js/', 'async_2.js is <em>not</em> included via &lt;script&gt;.');
    $this->assertNoPattern('/src=".*async_script_shim_test_sync_1\.js/', 'sync_1.js is <em>not</em> included via &lt;script&gt;.');
    $this->assertRaw('s.async = true;', 'Some script(s) <em>are</em> inserted via shim (will fail until <a href="http://drupal.org/node/1587536">#1587536</a> is resolved).');
    $this->assertPattern('/src=".*js\/js_.*"/', 'Some aggregated script(s) <em>are</em> included.');
    $this->assertPattern('/s.src = .*js\/js_.*;/', 'Some aggregated script(s) <em>are</em> inserted via shim (will fail until <a href="http://drupal.org/node/1587536">#1587536</a> is resolved).');
    $this->assertNoRaw('async="async"', '<em>No scripts use the async attribute.');
  }
}
