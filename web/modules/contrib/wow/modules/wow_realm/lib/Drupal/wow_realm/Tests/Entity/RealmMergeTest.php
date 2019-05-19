<?php

/**
 * @file
 * Definition of RealmMergeTest.
 */

namespace Drupal\wow_realm\Tests\Entity;

use WoW\Core\Response;

use Drupal\wow\Mocks\ServiceStub;
use Drupal\wow_realm\Mocks\RealmStorageControllerStub;
use Drupal\wow_realm\Tests\RealmUnitTestBase;

use WoW\Realm\Entity\RealmMerge;

/**
 * Tests the RealmMergeTest class.
 */
class RealmMergeTest extends RealmUnitTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Realm Merge',
      'description' => 'Unit Tests RealmMerge.',
      'group' => 'WoW Realm',
    );
  }

  public function testProcess() {
    // Creates a local realm.
    $storage = new RealmStorageControllerStub();
    $realm = $storage->create(array(
      'slug' => 'aegwynn',
      'name' => 'Aegwynn',
    ));

    // Fetches from local file system.
    $callback = new RealmMerge($storage, $realm);
    $service = new ServiceStub();
    $response = $service->newRequest('realm/status')->execute();

    $callback->process($service, $response);

    // Asserts data has been found and merged.
    $this->assertEqual($realm->type, 'pvp', 'Merged Aegwynn data.', 'WoW Realm');
  }

}
