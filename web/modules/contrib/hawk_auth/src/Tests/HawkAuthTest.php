<?php

/**
 * @file
 * Contains \Drupal\hawk_auth\Tests\HawkAuthTest.
 */

namespace Drupal\hawk_auth\Tests;

use Drupal\hawk_auth\Entity\HawkCredentialInterface;
use Drupal\simpletest\WebTestBase;
use Drupal\Core\Url;
use Drupal\user\UserInterface;

/**
 * Tests for basic hawk authentication.
 *
 * @group hawk_auth
 */
class HawkAuthTest extends WebTestBase {

  use HawkAuthTestTrait;

  /**
   * Modules installed for all tests.
   *
   * @var array
   */
  public static $modules = ['hawk_auth', 'hawk_route_tests'];

  /**
   * Account we'll be using for testing Hawk.
   *
   * @var UserInterface
   */
  protected $account;

  /**
   * Credentials for the above account we'll be using for testing Hawk.
   *
   * @var HawkCredentialInterface
   */
  protected $credential;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->account = $this->drupalCreateUser(['administer hawk']);
    $this->credential = $this->getHawkCredentials($this->account);
  }

  /**
   * Test basic hawk auth authentication response.
   */
  public function testHawkAuthResponse() {
    $url = Url::fromRoute('hawk_route_test.user');

    $this->hawkAuthGet($url, $this->credential);
    $this->assertText($this->account->getUsername(), 'Account name is displayed');
    $this->assertResponse('200', 'HTTP Response is okay');
    $this->curlClose();
    $this->assertFalse($this->drupalGetHeader('X-Drupal-Cache'));
    $this->assertIdentical(strpos($this->drupalGetHeader('Cache-Control'), 'public'), FALSE, 'Cache-Control is not set to public');

    $wrong_credential = clone $this->credential;
    $wrong_credential->setKeySecret('wrong_key');

    $this->hawkAuthGet($url, $wrong_credential);
    $this->assertNoText($this->account->getUsername(), 'Account name should not be displayed.');
    $this->assertResponse('403', 'HTTP Access is not granted');
    $this->curlClose();
  }

  /**
   * Test for protection against replay attacks.
   */
  public function testHawkReplayAttack() {
    $url = Url::fromRoute('hawk_route_test.user');

    $header = $this->getHawkAuthHeader($url, $this->credential);
    $this->hawkAuthGet($url, $this->credential, ['header' => $header]);
    $this->assertResponse('200', 'HTTP Response is okay');
    $this->assertText($this->account->getUsername(), 'Account name is displayed');

    $this->hawkAuthGet($url, $this->credential, ['header' => $header]);
    $this->assertResponse('403', 'HTTP Response is okay for nonce validation failure');
    $this->assertNoText($this->account->getUsername(), 'Account name should not be displayed.');
  }

  /**
   * Test for revoke permissions
   */
  public function testHawkRevokePermission() {
    $url = Url::fromRoute('hawk_route_test.permission_administer_hawk');

    $this->hawkAuthGet($url, $this->credential);
    $this->assertText($this->account->getUsername(), 'Account name is displayed for permission check');
    $this->assertResponse('200', 'HTTP Response is okay');

    $this->credential->setRevokePermissions(['administer hawk']);
    $this->credential->save();

    $this->hawkAuthGet($url, $this->credential);
    $this->assertNoText($this->account->getUsername(), 'Account should not be displayed');
    $this->assertResponse('403', 'HTTP Response is okay');
  }

}
