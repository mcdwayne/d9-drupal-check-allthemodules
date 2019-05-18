<?php

namespace Drupal\Tests\oauth2\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests OAuth2Controller functionality.
 *
 * @group oauth2
 */
class OAuth2ControllerTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['oauth2'];

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create users.
    $permissions = [
      'access administration pages',
      'administer site configuration',
      'view the administration theme',
      'administer oauth2',
    ];
    $account = $this->drupalCreateUser($permissions);

    // Initiate user session.
    $this->drupalLogin($account);
  }

  /**
   * Tests help message.
   */
  public function testHelp() {
    $this->drupalGet('admin/help/oauth2');
    $this->assertResponse(200);
    $this->assertRaw('The OAuth2.0 module supplies an <a href="https://tools.ietf.org/html/rfc6749">OAuth 2.0 Authorization</a> provider for web service requests.');
  }

}
