<?php

namespace Drupal\matomo\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\Component\Serialization\Json;

/**
 * Test custom url functionality of Matomo module.
 *
 * @group Matomo
 */
class MatomoCustomUrls extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['matomo'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $permissions = [
      'access administration pages',
      'administer matomo',
      'administer modules',
      'administer site configuration',
    ];

    // User to set up matomo.
    $this->admin_user = $this->drupalCreateUser($permissions);
  }

  /**
   * Tests if user password page urls are overridden.
   */
  public function testMatomoUserPasswordPage() {
    $base_path = base_path();
    $site_id = '1';
    $this->config('matomo.settings')->set('site_id', $site_id)->save();
    $this->config('matomo.settings')->set('url_http', 'http://www.example.com/matomo/')->save();
    $this->config('matomo.settings')->set('url_https', 'https://www.example.com/matomo/')->save();

    $this->drupalGet('user/password', ['query' => ['name' => 'foo']]);
    $this->assertRaw('_paq.push(["setCustomUrl", ' . Json::encode($base_path . 'user/password') . ']);');

    $this->drupalGet('user/password', ['query' => ['name' => 'foo@example.com']]);
    $this->assertRaw('_paq.push(["setCustomUrl", ' . Json::encode($base_path . 'user/password') . ']);');

    $this->drupalGet('user/password');
    $this->assertNoRaw('_paq.push(["setCustomUrl", "', '[testMatomoCustomUrls]: Custom url not set.');
  }

}
