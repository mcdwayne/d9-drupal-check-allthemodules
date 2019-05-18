<?php

namespace Drupal\cacheflush_entity\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test cacheflush entity functionality.
 *
 * @group cacheflush
 */
class CacheFlushEntityTest extends WebTestBase {

  /**
   * A user with permission to administer feeds and create content.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['cacheflush_entity'];

  /**
   * Sets up the test.
   */
  protected function setUp() {
    parent::setUp(self::$modules);
    $this->adminUser = $this->createUser();
  }

  /**
   * Tests CRUD functions for cacheflush entity.
   */
  public function testCrudFunctions() {

    $this->drupalLogin($this->adminUser);

    $user = $this->createUser();

    // Create test entities for the user and unrelated to a user.
    $entity = cacheflush_create(['title' => 'test']);
    $entity->setOwnerId($user->user_id);
    $entity->save();

    $entity = cacheflush_create(['title' => 'test2']);
    $entity->setOwnerId($this->adminUser->user_id);
    $entity->save();

    $entity = cacheflush_create(['title' => 'test']);
    $entity->setOwnerId(NULL);
    $entity->save();

    $entities = array_values(cacheflush_load_multiple_by_properties(['title' => 'test']));
    $this->assertEqual($entities[0]->getTitle(), 'test', 'Created and loaded entity.');
    $this->assertEqual($entities[1]->getTitle(), 'test', 'Created and loaded entity.');

    $loaded = cacheflush_load($entity->id());
    $this->assertEqual($loaded->id(), $entity->id(), 'Loaded the entity unrelated to a user.');

    $entities = array_values(cacheflush_load_multiple_by_properties(['title' => 'test2']));
    cacheflush_delete($entities[0]->id());

    $entities = array_values(cacheflush_load_multiple_by_properties(['title' => 'test2']));
    $this->assertEqual($entities, [], 'Entity successfully deleted.');

    $entity->save();
    $this->assertEqual($entity->id(), $loaded->id(), 'Entity successfully updated.');

    // Try deleting multiple test entities by deleting all.
    $ids = array_keys(cacheflush_load_multiple());
    cacheflush_delete_multiple($ids);

    $this->drupalLogout();
  }

}
