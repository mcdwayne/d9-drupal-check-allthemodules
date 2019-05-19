<?php

/**
 * @file
 * Definition of ExpiresArrayTest.
 */

namespace Drupal\wow\Tests\Data;

use WoW\Core\Data\ExpiresArray;

use Drupal\wow\Tests\UnitTestBase;

/**
 * Test ExpiresArray class.
 */
class ExpiresArrayTest extends UnitTestBase {

  public static function getInfo() {
    return array(
      'name' => 'ExpiresArray',
      'description' => 'Unit Tests ExpiresArray.',
      'group' => 'WoW',
    );
  }

  public function testPersist() {
    // Installs the wow_services table.
    drupal_install_schema('wow');
    wow_install();

    // Sets some data.
    $expires = new ExpiresArray(array(
      'fr' => array('wow_character_race' => 0),
      'en' => array('wow_character_race' => 0)
    ));
    $expires['en']['wow_character_race'] = 2592000;
    $expires['en']['wow_character_class'] = 1296000;

    // Triggers the __destruct() method.
    unset($expires);

    $service = db_select('wow_services', 's')
      ->fields('s', array('expires'))
      ->condition('language', 'en')
      ->execute()
      ->fetchObject();
    $expires = unserialize($service->expires);

    // Asserts values has been updated on object destruction.
    $this->assertEqual(2592000, $expires['wow_character_race'], 'DataService updated on object destruction.', 'WoW');
    $this->assertEqual(1296000, $expires['wow_character_class'], 'DataService updated on object destruction.', 'WoW');
  }

}
