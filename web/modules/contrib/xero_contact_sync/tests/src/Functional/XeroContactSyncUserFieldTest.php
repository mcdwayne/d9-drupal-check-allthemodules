<?php

namespace Drupal\Tests\xero_contact_sync\Functional;

use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\User;

/**
 * Tests the module provides the expected field in users.
 *
 * @group xero_contact_sync
 */
class XeroContactSyncUserFieldTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'user', 'xero_contact_sync'];

  public function testFieldExist() {
    $node = Node::create(['type' => 'article']);
    $fields = $node->getFields(FALSE);
    $this->assertArrayNotHasKey('xero_contact_id', $fields);

    $user = User::create([]);
    $fields = $user->getFields(FALSE);
    $this->assertArrayHasKey('xero_contact_id', $fields);
  }

}
