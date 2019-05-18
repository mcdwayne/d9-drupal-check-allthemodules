<?php

declare(strict_types = 1);

namespace Drupal\Tests\erg\Functional;

use Drupal\erg_test\Entity\OnPreValidateRefereeReferentAccessCheck;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\User;

/**
 * Tests entity reference access checks.
 *
 * @group ERG
 */
class OnPreValidateRefereeReferentAccessCheckTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['erg_test'];

  /**
   * Tests entity reference deletion.
   */
  public function test() {
    // Test with both saved and unsaved entities, to test EntityReference's
    // entity auto-loading.
    $user_unsaved = User::create([
        // Fake a user ID, because the default is 0, to which nobody has access.
      'uid' => 999999999,
      'name' => 'Foo',
      'status' => TRUE,
    ]);
    $user_saved = User::create([
      'name' => 'Bar',
      'status' => TRUE,
    ]);
    $user_saved->save();
    /** @var \Drupal\erg_test\Entity\OnPreValidateRefereeReferentAccessCheck $referee */
    $referee = OnPreValidateRefereeReferentAccessCheck::create();
    $referee->get('users')->appendItem($user_unsaved);
    $referee->get('users')->appendItem($user_saved);

    // A user without access to viewing others must not be able to reference
    // them.
    $current_user_without_access = $this->drupalCreateUser();
    $this->drupalLogin($current_user_without_access);
    $this->assertNotEmpty($referee->validate());

    // A user with access to viewing others must be able to reference them.
    $current_user_with_access = $this->drupalCreateUser(['access user profiles']);
    $this->drupalLogin($current_user_with_access);
    $this->assertEmpty($referee->validate());
  }

}
