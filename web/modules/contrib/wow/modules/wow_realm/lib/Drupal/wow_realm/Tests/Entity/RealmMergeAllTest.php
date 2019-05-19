<?php

/**
 * @file
 * Definition of RealmMergeAllTest.
 */

namespace Drupal\wow_realm\Tests\Entity;

use WoW\Core\Response;

use Drupal\wow\Mocks\ServiceStub;
use Drupal\wow_realm\Mocks\RealmStorageControllerStub;
use Drupal\wow_realm\Tests\RealmUnitTestBase;

use WoW\Realm\Entity\RealmMerge;
use WoW\Realm\Entity\RealmMergeAll;

/**
 * Tests the RealmMergeAllTest class.
 */
class RealmMergeAllTest extends RealmUnitTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Realm Merge All',
      'description' => 'Unit Tests RealmMergeAll.',
      'group' => 'WoW Realm',
    );
  }

  public function testProcess() {
    // Creates two local realms.
    $storage = new RealmStorageControllerStub();
    $storage->create(array(
      'slug' => 'aegwynn',
      'name' => 'Aegwynn',
    ));
    $storage->create(array(
      'slug' => 'aerie-peak',
      'name' => 'Aerie Peak',
    ));
    // This realm is a non-existing one.
    $storage->create(array(
      'slug' => 'non-existing-realm',
      'name' => 'Non Existing Realm',
    ));

    // Fetches from local file system the whole list of realms.
    $callback = new RealmMergeAll($storage);
    $service = new ServiceStub();
    $response = $service->newRequest('realm/status')->execute();

    $callback->process($service, $response);

    // Asserts the two former realms has been recognized, and that the later is
    // considered as new.
    $entities = $storage->load(FALSE);
    $this->assertTrue($entities[1]->locale == 'de_DE', 'Updated Aegwynn server.', 'WoW Realm');
    $this->assertTrue($entities[2]->locale == 'en_GB', 'Updated Aerie Peak server.', 'WoW Realm');
    $this->assertTrue(empty($entities[3]), 'Deleted Wrong server.', 'WoW Realm');
    $this->assertTrue($entities[4]->locale == 'en_GB', 'Imported Agamaggan server.', 'WoW Realm');
    $this->assertEqual(sizeof($entities), 267, 'Size of database is 267.', 'WoW Realm');
  }

}
