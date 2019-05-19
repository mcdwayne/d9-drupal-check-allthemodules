<?php

/**
 * @file
 * Definition of BattleGroupMergeTest.
 */

namespace Drupal\wow_realm\Tests\Entity;

use Drupal\wow_realm\Mocks\BattleGroupStorageControllerStub;
use Drupal\wow\Mocks\ServiceStub;
use Drupal\wow_realm\Tests\RealmUnitTestBase;

use WoW\Core\Data\DataService;
use WoW\Realm\Entity\BattleGroupMerge;

/**
 * Tests the RealmServiceController class.
 */
class BattleGroupMergeTest extends RealmUnitTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Battle Group Merge',
      'description' => 'Unit Tests BattleGroupMerge.',
      'group' => 'WoW Realm',
    );
  }

  public function testProcess() {
    // Creates two local realms.
    $storage = new BattleGroupStorageControllerStub();
    $storage->create(array(
      'slug' => 'battle-net-invitational-bg',
      'name' => 'Battle net Invitational BG',
    ));
    // This realm is a non-existing one.
    $storage->create(array(
      'slug' => 'non-existing-battle-group',
      'name' => 'Non Existing Battle Group',
    ));

    // Fetches from local file system the whole list of realms.
    $callback = new BattleGroupMerge($storage);
    $service = new DataService(new ServiceStub(), array());
    $response = $service->newRequest('data/battlegroups/')->execute();

    $callback->process($service, $response);

    // Asserts the two former realms has been recognized, and that the later is
    // considered as new.
    $entities = $storage->load(FALSE);
    $this->assertTrue($entities[1]->name == 'Battle net Invitational BG', 'Found Battle net Invitational BG Battle Group.', 'WoW Realm');
    $this->assertTrue(empty($entities[2]), 'Deleted Non Existing Battle Group.', 'WoW Realm');
    $this->assertTrue($entities[3]->name == 'Blackout', 'Imported Blackout Battle Group.', 'WoW Realm');
    $this->assertEqual(sizeof($entities), 19, 'Size of database is 19.', 'WoW Realm');
  }

}
