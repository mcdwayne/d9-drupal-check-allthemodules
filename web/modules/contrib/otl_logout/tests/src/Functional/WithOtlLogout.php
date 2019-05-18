<?php

namespace Drupal\Tests\otl_logout\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Url;

/**
 * Verify that the intended behavior works when the module is enabled.
 *
 * @group otl_logout
 */
class WithOtlLogout extends WithoutOtlLogout {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    // Modules for core functionality.
    'node',
    'taxonomy',
    'user',

    // This module is enabled for this scenario.
    'otl_logout',
  ];

  /**
   * Test the results of loading the One Time Login path.
   *
   * With this scenario the request should work and the second user should be
   * logged in.
   */
  public function testOTL() {
    // Confirm the page loaded correctly.
    $this->assertResponse(200);

    // Confirm the normal OTL message is present.
    $this->assertText('You have just used your one-time login link. It is no longer necessary to use this link to log in. Please change your password.');

    // Confirm that the user being edited is the second user account.
    $this->assertFieldByName('mail', $this->account2->getEmail());
  }

}
