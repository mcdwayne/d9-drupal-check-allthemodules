<?php

namespace Drupal\Tests\xero_contact_sync\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\User;

/**
 * Tests the module queues when there is a content created.
 *
 * @group xero_contact_sync
 */
class XeroContactSyncQueuingTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'user', 'xero_contact_sync'];

  public function testQueuingWhenUserIsCreated() {
    $user = User::create([
      'uid' => 23,
      'name' => 'Jordan',
    ]);
    $user->save();

    $user = User::create([
      'uid' => 33,
      'name' => 'Bird',
      'xero_contact_id' => 33,
    ]);
    $user->save();

    // There is only one, the other already had a xero_contact_id.
    $queue = \Drupal::queue('xero_contact_sync_create');
    $this->assertEquals(1, $queue->numberOfItems());
    $item = $queue->claimItem();
    $this->assertEquals($item->data['user_id'], 23);
  }

}
