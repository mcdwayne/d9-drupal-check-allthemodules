<?php

declare(strict_types = 1);

namespace Drupal\Tests\erg\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests entity reference deletion.
 *
 * @group ERG
 */
class OnDeleteReferentDeleteRefereeTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['erg_test'];

  /**
   * Tests entity reference deletion.
   */
  public function test() {
    $storage = \Drupal::entityTypeManager()->getStorage('erg_test_odrdreferee');
    $user = $this->drupalCreateUser();
    /** @var \Drupal\erg_test\Entity\OnDeleteReferentDeleteReferee $referee */
    $referee = $storage->create();
    $referee->get('users')->appendItem($user);
    $this->assertEmpty($referee->validate());
    $referee->save();

    // Confirm the reference was saved.
    $referee = $storage->loadUnchanged($referee->id());
    $users = $referee->get('users')->referencedEntities();
    $this->assertSame($user->id(), $users[0]->id());

    $user->delete();

    // Confirm the referee was deleted.
    $referee = $storage->loadUnchanged($referee->id());
    $this->assertNull($referee);
  }

}
