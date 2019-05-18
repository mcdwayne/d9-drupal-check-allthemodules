<?php

namespace Drupal\Tests\access_by_entity\Unit;

use Drupal\access_by_entity\AccessByEntityStorageInterface;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\KernelTests\KernelTestBase;

/**
 * Class AccessByEntityTest.
 *
 * @group access_by_entity
 *
 * @todo Need more functionnal tests.
 */
class AccessByEntityTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['access_by_entity', 'node', 'user'];

  /**
   * Test access_by_entity.access_storage service with all declared function.
   */
  public function testAccessStorageService() {
    $this->installSchema('access_by_entity', 'access_by_entity');
    $this->installEntitySchema('node');
    // Get access storage service.
    $access_storage = $this->container->get('access_by_entity.access_storage');

    // Service must implement AccessByEntityStorageInterface Interface.
    $this->assertInstanceOf(
      AccessByEntityStorageInterface::class, $access_storage
    );

    // Default available permission in module.
    $default_settings = ['view', 'edit', 'delete'];

    foreach ($default_settings as $perm) {

      // Test save function.
      $this->assertTrue(
        $access_storage->save(
          555, 'node', 'anonymous', [$perm => 1]
        )
      );

      // Should return array entity ID 555 already saved.
      $this->assertNotEmpty(
        $access_storage->findBy(
          [
            ['key' => 'entity_id', 'value' => 555],
            ['key' => 'perm', 'value' => $perm],
            ['key' => 'rid', 'value' => 'anonymous'],
            ['key' => 'entity_type_id', 'value' => 'node'],
          ]
        )
      );

      // Should return Empty array.
      $this->assertEmpty(
        $access_storage->findBy(
          [
            ['key' => 'entity_id', 'value' => 999],
            ['key' => 'perm', 'value' => $perm],
            ['key' => 'rid', 'value' => 'anonymous'],
            ['key' => 'entity_type_id', 'value' => 'node'],
          ]
        )
      );

      // Test isAccessAllowed function.
      $this->assertFALSE($access_storage->isAccessAllowed(555, 'node', $perm));
      // Delete 555 Id and try again to check access permission.
      $this->assertTrue($access_storage->clear(555, 'node'));

      // Should return true.
      $this->assertTRUE($access_storage->isAccessAllowed(555, 'node', $perm));
    }

  }

}
