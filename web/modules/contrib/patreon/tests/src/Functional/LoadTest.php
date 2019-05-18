<?php

namespace Drupal\Tests\patreon\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Simple test to ensure that main page loads with module enabled.
 *
 * @group patreon
 */
class LoadTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['patreon'];

  /**
   * A user with permission to administer Patreon.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->user = $this->drupalCreateUser(['administer patreon']);
    $this->drupalLogin($this->user);
  }

  /**
   * Tests that the home page loads with a 200 response.
   */
  public function testLoad() {
    $this->drupalGet(Url::fromRoute('<front>'));
    $this->assertResponse(200);
  }

  /**
   * Returns a fake array matching a Patreon API return.
   *
   * @return array
   *   Array of faked API details.
   */
  public function getTokenReturn() {
    return array(
      "access_token" => "PAtrEOnPAtR3onPatRe0nPAt43OnpA",
      "expires_in" => 2678400,
      "token_type" => "Bearer",
      "scope" => "users pledges-to-me my-campaign",
      "refresh_token" => "patrEoNP4TrE0Np4TrEoNpatreOnpa",
      "version" => "0.0.1",
    );
  }

}
