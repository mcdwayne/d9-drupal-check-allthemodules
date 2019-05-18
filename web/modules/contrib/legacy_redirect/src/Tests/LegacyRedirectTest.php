<?php

namespace Drupal\legacy_redirect\Tests;

use Drupal\simpletest\WebTestBase;

class LegacyRedirectTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['legacy_redirect'];

  public static function getInfo() {
    return [
      'name' => 'Legacy Redirects',
      'description' => 'Test that q=path/to/page requests are properly redirected.',
      'group' => 'Legacy Redirect'
    ];
  }

  /**
   * Test that a request to a q=path/to/page properly redirects.
   */
  function testLegacyRedirects() {
    global $base_url;

    // Test with q as sole query parameter.
    $this->drupalGet(NULL, ['query' => ['q' => 'user/register']]);
    $this->assertEqual($base_url . '/index.php/user/register', $this->getAbsoluteUrl($this->getUrl()));
    $this->assertText(t('Create new account'), 'The request was successfully redirected to the user registration page.');

    // Test with additional query parameters.
    $this->drupalGet(NULL, ['query' => ['q' => 'user/register', 'a' => 'b']]);
    $this->assertEqual($base_url . '/index.php/user/register?a=b', $this->getAbsoluteUrl($this->getUrl()));
  }
}
