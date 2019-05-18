<?php

/**
 * @file
 * Contains \Drupal\docker\Tests\DockerApiTest.
 */

namespace Drupal\docker\Tests;

use Drupal\docker\DockerApi;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the JsonEncoder class.
 *
 * @see \Drupal\docker\DockerApi
 */
class DockerApiTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'DockerApiTest',
      'description' => 'Tests the DockerApi class.',
      'group' => 'Docker',
    );
  }

  /**
   * Tests the getImages() method.
   */
  public function testGetImages() {
    $this->assertTrue(FALSE);
  }

}
