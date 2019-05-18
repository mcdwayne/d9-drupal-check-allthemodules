<?php

/**
 * @file
 * Contains \Drupal\acl\Tests\AclMigrationTestTrait.
 */

namespace Drupal\acl\Tests;

use Drupal\node\Entity\Node;

/**
 * Provides common functionality for the ACL Migration test classes.
 */
trait AclMigrationTestTrait {

  /**
   * Provides information about database dumps directory.
   */
  protected function getAclDumpDirectory() {
    return __DIR__ . '/Table';
  }

  /**
   * Tests migration of ACL List.
   */
  public function testMigration() {
    // Checking `number` to `figure` migration.
    $acl_id = acl_get_id_by_name('acl_node_test', 'test_name', 123);
    $this->assertNotEqual($acl_id, FALSE);

    // One more check.
    $acl_id = acl_get_id_by_figure('acl_node_test', 5);
    $this->assertEqual($acl_id, 2);

    // Testing `acl_user` migration.
    $this->assertEqual(acl_has_user(1, 1), TRUE);
    $this->assertEqual(acl_has_user(2, 1), TRUE);
    $this->assertEqual(acl_has_user(1, 2), TRUE);
    $this->assertNotEqual(acl_has_user(2, 2), TRUE);

    // Testing first migrated node grants.
    $node = Node::load(1);
    $this->assertNotNull($node, "Node 1 can be loaded");
    $grants = \Drupal::entityManager()
      ->getAccessControlHandler('node')
      ->acquireGrants($node);
    $acl_grant_exists = FALSE;
    foreach ($grants as $grant) {
      if ($grant['realm'] == 'acl' && $grant['grant_update'] == TRUE && $grant['priority'] == 5) {
        $acl_grant_exists = TRUE;
      }
    }
    $this->assertEqual($acl_grant_exists, TRUE);

    // Testing second migrated node grants.
    $node = Node::load(2);
    $grants = \Drupal::entityManager()
      ->getAccessControlHandler('node')
      ->acquireGrants($node);
    $acl_grant_count = 0;
    foreach ($grants as $grant) {
      if ($grant['realm'] == 'acl') {
        if ($grant['grant_view'] == TRUE && $grant['grant_delete'] == TRUE && $grant['priority'] == 10) {
          $acl_grant_count++;
        }
        if ($grant['grant_view'] == TRUE && $grant['grant_update'] == TRUE && $grant['priority'] == 8) {
          $acl_grant_count++;
        }
      }
    }
    $this->assertEqual($acl_grant_count, 2);
  }

}
