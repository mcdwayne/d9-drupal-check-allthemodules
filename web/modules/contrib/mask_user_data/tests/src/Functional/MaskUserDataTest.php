<?php

namespace Drupal\Tests\mask_user_data\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\User as DUser;

/**
 * Tests masking user data.
 *
 * @group mask_user_data
 */
class MaskUserDataTest extends BrowserTestBase {

  /**
   * Enabled modules.
   *
   * @var modules
   */
  public static $modules = ['mask_user_data'];

  /**
   * Test single user masking.
   */
  public function testMaskUserDataMaskSingleUser() {
    $user = $this->drupalCreateUser();
    $mask_map = [
      'mail' => 'email',
    ];
    $this->container->get('mask_user_data.mask_user')->mask($user->id(), $mask_map);
    $masked_user = DUser::load($user->id());

    $this->assertNotEquals($user->getEmail(), $masked_user->getEmail());
    $this->assertEquals($user->getUsername(), $masked_user->getUsername());
  }

}
