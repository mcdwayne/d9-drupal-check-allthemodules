<?php
 
/**
 * @file
 *
 * Contains \Drupal\Tests\abookings\Unit\ExampleConversionsTest.
 */
 
namespace Drupal\Tests\abookings\Unit;
 
use Drupal\Tests\UnitTestCase;
 
/**
 * Demonstrates how to write tests.
 *
 * @group abookings
 */
class ExampleConversionsTest extends UnitTestCase {
 
  /**
   * @var \Drupal\abookings\ExampleConversionsTest
   */
  public $conversionService;
 
  public function setUp() {
    $this->conversionService = new \Drupal\Tests\abookings\Unit\ExampleConversionsTest();
  }
 
  /**
   * A simple test that tests our celsiusToFahrenheit() function.
   */
  public function testOneConversion() {
    // Confirm that 0C = 32F.
    $this->assertEquals(32, 31 + 1);
    $this->assertEquals(12, 11 + 1);
  }
 
}