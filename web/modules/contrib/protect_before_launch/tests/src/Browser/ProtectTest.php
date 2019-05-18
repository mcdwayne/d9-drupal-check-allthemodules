<?php

namespace Drupal\Tests\protect_before_launch\Browser;

use Drupal\protect_before_launch\Configuration;
use Drupal\Tests\BrowserTestBase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ProtectTest.
 *
 * @group tests
 * @package Drupal\Tests\protect_before_launch\Browser
 */
class ProtectTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['protect_before_launch'];

  /**
   * Realm.
   *
   * @var string
   */
  private $realm = 'Protected Site';

  /**
   * Content.
   *
   * @var string
   */
  private $content = 'Access Denied';

  /**
   * Test that protection is disabled.
   */
  public function testDisabled() {
    $this->drupalGet('');
    $this->assertAccessGranted();
  }

  /**
   * Test protection enabled.
   */
  public function testEnabled() {
    $this->updateConfig(['protect' => Configuration::PROTECT_ENABLED]);
    $this->drupalGet('');
    $this->assertAccessDenied();
  }

  /**
   * Test protection enabled.
   */
  public function testAuthentication() {
    $this->updateConfig(['protect' => Configuration::PROTECT_ENABLED]);
    $this->drupalGet('');
    $this->assertAccessDenied();

    $this->drupalGet('', [], [
      'PHP_AUTH_USER' => 'username',
      'PHP_AUTH_PW' => 'password'
    ]);
    $this->assertAccessGranted();
  }

  /**
   * Test Customizing values.
   */
  public function testChangeContent() {

    $this->content = $this->realm = time();
    $this->updateConfig([
      'protect' => Configuration::PROTECT_ENABLED,
      'content' => $this->content,
      'realm' => $this->realm,
    ]);

    $this->drupalGet('');

    $this->assertAccessDenied();
  }

  /**
   * Update module configuration.
   *
   * @param array $values
   *   Config values.
   */
  private function updateConfig(array $values) {
    $config = $this->config('protect_before_launch.settings');
    foreach ($values as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();
  }

  /**
   * Assert this page can not be loaded without user authorization.
   */
  private function assertAccessDenied() {
    $unauthorizedHeader = FALSE;

    foreach ($this->getSession()->getResponseHeaders() as $name => $values) {
      if (stripos($name, 'www-authenticate') !== FALSE
        && $values[0] === sprintf('Basic realm="%s"', $this->realm)
      ) {
        $unauthorizedHeader = TRUE;
      }
    }

    $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->getSession()->getStatusCode());
    $this->assertTrue($unauthorizedHeader, 'WWW-Authenticate header to be present');
    $this->assertEquals($this->content, $this->getSession()->getPage()->getContent());
  }

  /**
   * Assert this page can be loaded without user authorization.
   */
  private function assertAccessGranted() {
    $unauthorizedHeader = FALSE;

    foreach ($this->getSession()->getResponseHeaders() as $name => $values) {
      if (stripos($name, 'www-authenticate') !== FALSE) {
        $unauthorizedHeader = TRUE;
      }
    }

    $this->assertEquals(Response::HTTP_OK, $this->getSession()->getStatusCode());
    $this->assertFalse($unauthorizedHeader, 'WWW-Authenticate header needs to be absent');
  }

}
