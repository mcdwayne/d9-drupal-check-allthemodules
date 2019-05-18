<?php

namespace Drupal\supercache\Tests\Generic\Cache;

use Drupal\Core\Cache\CacheBackendInterface;

use Drupal\Component\Utility\Unicode;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Database\Database;

use Drupal\Core\Site\Settings;

/**
 * Test saving functions.
 *
 * @group Cache
 */
class BackendSavingTests extends BackendGeneralTestCase {

  public static function getInfo() {
    return [
      'name' => 'Saving tests',
      'description' => 'Ensure that cache items are properly stored and retrieved.',
      'group' => 'Couchbase',
    ];
  }

  /**
   * Test the saving and restoring of a string.
   */
  public function testString() {
    $this->checkVariable($this->randomName(100));
  }

  /**
   * Test the saving and restoring of an integer.
   */
  public function testInteger() {
    $this->checkVariable(100);
  }

  /**
   * Test the saving and restoring of a double.
   */
  public function testDouble() {
    $this->checkVariable(1.29);
  }

  /**
   * Test the saving and restoring of an array.
   */
  public function testArray() {
    $this->checkVariable(
      array(
        'drupal1' => '', 'drupal2' => 'drupal3',
        'drupal4' => array('drupal5', 'drupal6'),
      )
    );
  }

  /**
   * Test the saving and restoring of an object.
   */
  public function testObject() {
    $test_object = new \stdClass();
    $test_object->test1 = $this->randomName(100);
    $test_object->test2 = 100;
    $test_object->test3 = array(
      'drupal1' => '', 'drupal2' => 'drupal3',
      'drupal4' => array('drupal5', 'drupal6'),
    );

    $this->backend->set('test_object', $test_object);
    $cache = $this->backend->get('test_object');
    $this->assertTrue(isset($cache->data) && $cache->data == $test_object, 'Object is saved and restored properly.');
  }

}