<?php

namespace Drupal\Tests\mailjet\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests core API Mailjet functionality.
 *
 * @group mailjet
 */
class MailjetAPITest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['mailjet'];

  /**
   * Tests that the test API has been loaded.
   */
  function testAPI() {
    $mailjet_object_api = mailjet_new();

    $this->assertNotNull($mailjet_object_api);

    $this->assertEqual(get_class($mailjet_object_api), 'Mailjet\Mailjet');
  }

}
