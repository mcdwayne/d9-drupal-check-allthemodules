<?php

/**
 * @file
 * Tests for Async script shim module.
 */

namespace Drupal\async_script_shim\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test basic functionality of Async script shim module.
 */
class AsyncScriptShimDisabledTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('async_script_shim_test');

  /**
   * Implements getInfo().
   */
  public static function getInfo() {
    return array(
      'name' => t('Async script shim tests without shim enabled.'),
      'description' => t('Test asyncs scripts without Async script shim module enabled.'),
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
   * Check scripts with module disabled and scripts not aggregated.
   */
  public function testAsyncScriptShimDisabledNonAggregated() {
    config('system.performance')
      ->set('preprocess.js', FALSE)
      ->save();

    $this->drupalGet('');
    $this->assertPattern('/src=".*async_script_shim_test_async_1\.js/', 'async_1.js <em>is</em> included via &lt;script&gt;.');
    $this->assertPattern('/src=".*async_script_shim_test_async_2\.js/', 'async_2.js <em>is</em> included via &lt;script&gt;.');
    $this->assertPattern('/src=".*async_script_shim_test_sync_1\.js/', 'sync_1.js <em>is</em> included via &lt;script&gt;.');
    $this->assertNoRaw('s.async = true;', '<em>No</em> script(s) are included via shim.');
    $this->assertNoPattern('/src=".*js\/js_.*"/', '<em>No</em> aggregated script(s) are included.');
    $this->assertNoPattern('/s.src = .*js\/js_.*;/', '<em>No</em> aggregated script(s) are included via shim.');
    $this->assertRaw('async="async"', '<em>Some</em> scripts use the async attribute.');
  }

  /**
   * Check scripts with module disabled and scripts aggregated.
   */
  public function testAsyncScriptShimDisabledAggregated() {
    config('system.performance')
      ->set('preprocess.js', TRUE)
      ->save();

    $this->drupalGet('');
    $this->assertNoPattern('/src=".*async_script_shim_test_async_1\.js/', 'async_1.js is <em>not</em> included via &lt;script&gt;.');
    $this->assertNoPattern('/src=".*async_script_shim_test_async_2\.js/', 'async_2.js is <em>not</em> included via &lt;script&gt;.');
    $this->assertNoPattern('/src=".*async_script_shim_test_sync_1\.js/', 'sync_1.js is <em>not</em> included via &lt;script&gt;.');
    $this->assertNoRaw('s.async = true;', '<em>No</em> script(s) are included via shim.');
    $this->assertPattern('/src=".*js\/js_.*"/', '<em>Some</em> aggregated script(s) are included.');
    $this->assertNoPattern('/s.src = .*js\/js_.*;/', '<em>No</em> aggregated script(s) are included via shim.');
    $this->assertRaw('async="async"', 'Some scripts <em>use</em> the async attribute (will fail until <a href="http://drupal.org/node/1587536">#1587536</a> is resolved).');
  }
}
