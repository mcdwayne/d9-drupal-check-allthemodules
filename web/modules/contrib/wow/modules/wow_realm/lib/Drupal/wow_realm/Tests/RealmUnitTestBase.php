<?php

/**
 * @file
 * Helper class for Realm unit test base classes.
 */

namespace Drupal\wow_realm\Tests;

use Drupal\wow\Mocks\ServiceStub;
use Drupal\wow\Tests\UnitTestBase;

use WoW\Realm\Entity\Realm;
use WoW\Realm\Entity\RealmServiceController;

/**
 * Defines UnitTestBase class test.
 */
class RealmUnitTestBase extends UnitTestBase {

  protected function setUp() {
    parent::setUp();

    $this->registerNamespace('WoW\Realm', 'wow_realm');
    $this->registerNamespace('Drupal\wow_realm', 'wow_realm');

    drupal_load('module', 'wow_realm');
    $this->entityInfos += wow_realm_entity_info();
  }

  /**
   * Creates a new Realm entity.
   *
   * @param array $values
   *   The entity values.
   *
   * @return Realm
   *   A new instance of wow_realm entity.
   */
  protected function createRealm(array $values) {
    return new Realm($values, 'wow_realm');
  }

}
