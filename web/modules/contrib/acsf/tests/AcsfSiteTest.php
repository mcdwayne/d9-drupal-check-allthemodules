<?php

/**
 * @file
 * Provides PHPUnit tests for Acsf Site.
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/AcsfVariableStorageMock.php';

/**
 * AcsfSiteTest.
 */
class AcsfSiteTest extends TestCase {

  /**
   * The site ID issued by the factory.
   *
   * @var int
   */
  public $site_id = 12345678;

  /**
   * Setup.
   */
  public function setUp() {
    // Simulate the sites.json configuration.
    $GLOBALS['gardens_site_settings']['conf']['acsf_site_id'] = $this->site_id;

    $drupalMock = Mockery::mock('overload:Drupal');
    $drupalMock->shouldReceive('service')
      ->with('acsf.variable_storage')
      ->once()
      ->andReturn(new AcsfVariableStorageMock());
  }

  /**
   * Provides test data.
   */
  public function getTestData() {
    $data = [
      'true' => TRUE,
      'false' => FALSE,
      'string' => 'unit_test_string_value',
      'int' => mt_rand(0, 64),
      'float' => mt_rand() / mt_getrandmax(),
      'array' => ['foo', 'bar', 'baz', 'qux'],
    ];

    $data['object'] = (object) $data;

    return $data;
  }

  /**
   * Tests that we can use the factory method to get a cached site.
   */
  public function testFactoryLoadCache() {
    $site = \Drupal\acsf\AcsfSite::load();
    $this->assertInstanceOf('\Drupal\acsf\AcsfSite', $site);

    $cache = \Drupal\acsf\AcsfSite::load();
    $this->assertSame($site, $cache);
    $this->assertEquals($site->site_id, $cache->site_id);
  }

  /**
   * Tests the __get() method.
   *
   * Test the public interface by using the __set() directly and then checking
   * if the value is set for the class property.
   */
  public function testAcsfSiteGet() {
    $site = new \Drupal\acsf\AcsfSite($this->site_id);

    $data = $this->getTestData();

    foreach ($data as $type => $value) {
      $site->__set($type, $value);
      $this->assertSame($site->$type, $value);
    }
  }

  /**
   * Tests the __set() method.
   *
   * Test the public interface by setting a class property, then checking if
   * the value is available using the __get() method.
   */
  public function testAcsfSiteSet() {
    $site = new \Drupal\acsf\AcsfSite($this->site_id);

    $data = $this->getTestData();

    foreach ($data as $type => $value) {
      $site->$type = $value;
      $this->assertSame($site->__get($type), $value);
    }
  }

  /**
   * Tests the __unset() method.
   *
   * Test the public interface by first setting a class property, and assuring
   * that it is available using the __get() method. Then uset that same class
   * property and assure that it is NOT available using the __get() method.
   */
  public function testAcsfSiteUnset() {
    $site = new \Drupal\acsf\AcsfSite($this->site_id);

    $data = $this->getTestData();

    foreach ($data as $type => $value) {
      $site->$type = $value;
      $this->assertSame($site->__get($type), $value);
      unset($site->$type);
      $get_value = $site->__get($type);
      $this->assertNull($get_value);
    }
  }

  /**
   * Tests the __isset() method.
   *
   * Test the public interface by first setting a value using the __set()
   * method and assuring that it is available in the __get() method. Then test
   * that the class property is set using isset().
   */
  public function testAcsfSiteIsset() {
    $site = new \Drupal\acsf\AcsfSite($this->site_id);

    $data = $this->getTestData();

    foreach ($data as $type => $value) {
      $site->__set($type, $value);
      $this->assertSame($site->__get($type), $value);
      $this->assertTrue(isset($site->$type));
    }
  }

  /**
   * Tests the save() method.
   */
  public function testSavedData() {
    $string = 'test value';
    $site = new \Drupal\acsf\AcsfSite($this->site_id);
    $site->custom = $string;
    $site->save();
    unset($site);

    $clone = new \Drupal\acsf\AcsfSite($this->site_id);
    $this->assertEquals($clone->custom, $string);
  }

  /**
   * Cleanup Mockery on each test. (PHPUnit 5 does not support listeners.)
   */
  public function tearDown() {
    Mockery::close();
  }

}
