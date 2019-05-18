<?php

namespace Drupal\Tests\open_connect\Functional;

use Drupal\Tests\BrowserTestBase;

class LoginBlockTest extends BrowserTestBase {

  /**
   * Modules to install.
   */
  protected static $modules = [
    'block',
    'open_connect',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->config('open_connect.settings')
      ->set('providers', [
        'wechat_mp' => [
          'mode' => 'live',
          'client_id' => 'test_client_id',
          'client_secret' => 'test_client_secret',
          'scope' => 'test scope',
        ],
        'wechat_op' => [
          'mode' => 'live',
          'client_id' => 'test_client_id2',
          'client_secret' => 'test_client_secret2',
          'scope' => 'test scope2',
        ],
      ])
      ->save();

    $this->placeBlock('open_connect_login');
  }

  /**
   * Tests the identity providers.
   */
  public function testLoginBlock() {
    $this->drupalGet('<front>');
    // file_put_contents('/home/edward/Desktop/out.html', $this->drupalGet('<front>'));
    $web_assert = $this->assertSession();
    $web_assert->buttonExists('Log in with WeChat MP');
    $web_assert->buttonExists('Log in with WeChat OP');
    $web_assert->buttonNotExists('Log in with Weibo');

    // Add weibo.
    $providers = $this->config('open_connect.settings')->get('providers');
    $providers['weibo'] = [
      'mode' => 'live',
      'client_id' => 'test_client_id3',
      'client_secret' => 'test_client_secret3',
    ];
    $this->config('open_connect.settings')->set('providers', $providers)->save();
    $this->drupalGet('<front>');
    $web_assert = $this->assertSession();
    $web_assert->buttonExists('Log in with WeChat MP');
    $web_assert->buttonExists('Log in with WeChat OP');
    $web_assert->buttonExists('Log in with Weibo');

    // Login.
    $this->drupalLogin($this->createUser());
    $this->drupalGet('<front>');
    $web_assert = $this->assertSession();
    $web_assert->buttonNotExists('Log in with WeChat MP');
    $web_assert->buttonNotExists('Log in with WeChat OP');
    $web_assert->buttonNotExists('Log in with Weibo');
  }

}
