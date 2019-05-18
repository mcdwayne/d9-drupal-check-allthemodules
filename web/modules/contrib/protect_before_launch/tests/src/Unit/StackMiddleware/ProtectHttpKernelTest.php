<?php

namespace Drupal\Tests\protect_before_launch\Unit\StackMiddleware;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\Core\Password\PasswordInterface;
use Drupal\protect_before_launch\Configuration;
use Drupal\protect_before_launch\StackMiddleware\ProtectHttpKernel;
use Drupal\Tests\protect_before_launch\Unit\UnitTestCase;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Class ProtectEventSubscriberTest.
 *
 * @group tests
 * @package Drupal\Tests\protect_before_launch\Unit\StackMiddleware
 */
class ProtectHttpKernelTest extends UnitTestCase {

  /**
   * Test access granted when disabled.
   */
  public function testAccessGrantedWhenDisabled() {
    $response = $this->handleRequestWithConfig(new Request(), array_merge($this->getProtectedConfig(), [
      'protect' => Configuration::PROTECT_DISABLED
    ]));
    $this->assertEmpty($response);
  }

  /**
   * Test access denied when protected when requested without credentials.
   */
  public function testAccessDeniedWhenProtectedWithoutCredentials() {
    $response = $this->handleRequestWithConfig(new Request(), $this->getProtectedConfig());
    $this->assertProtected($response);
  }

  /**
   * Test access granted when protected with valid credentials.
   */
  public function testAccessGrantedWhenProtectedWithValidCredentials() {
    $request = new Request();
    $request->headers->set('PHP_AUTH_USER', 'foo');
    $request->headers->set('PHP_AUTH_PW', 'bar');

    $response = $this->handleRequestWithConfig($request, array_merge($this->getProtectedConfig(), [
      'authentication_type' => Configuration::AUTH_SIMPLE,
      'username' => 'foo',
      'password' => password_hash('bar', PASSWORD_DEFAULT)
    ]));

    $this->assertEmpty($response);
  }

  /**
   * Test access denied when protected with invalid credentials.
   */
  public function testAccessDeniedWhenProtectedWithInvalidCredentials() {
    $request = new Request();
    $request->headers->set('PHP_AUTH_USER', 'foo');
    $request->headers->set('PHP_AUTH_PW', 'ber');

    $response = $this->handleRequestWithConfig($request, array_merge($this->getProtectedConfig(), [
      'authentication_type' => Configuration::AUTH_SIMPLE,
      'username' => 'foo',
      'password' => password_hash('bar', PASSWORD_DEFAULT)
    ]));

    $this->assertProtected($response);
  }

  /**
   * Test access granted when protected without credentials and a matching exclude path.
   */
  public function testAccessGrantedWhenProtectedWithoutCredentialsWithMatchingExcludePath() {
    $response = $this->handleRequestWithConfig(Request::create('/foo'), array_merge($this->getProtectedConfig(), [
      'exclude_paths' => '^/foo'
    ]));
    $this->assertEmpty($response);
  }

  /**
   * Test access denied when protected without credentials without matching exclude path.
   */
  public function testAccessDeniedWhenProtectedWithoutCredentialsWithoutMatchingExcludePath() {
    $response = $this->handleRequestWithConfig(Request::create('/foo'), array_merge($this->getProtectedConfig(), [
      'exclude_paths' => '^/bar'
    ]));
    $this->assertProtected($response);
  }

  /**
   * Test access denied when protected with Drupal as identity provider.
   */
  public function testAccessDeniedWhenProtectedFromDrupal() {
    $request = new Request();
    $request->headers->set('PHP_AUTH_USER', 'foo');
    $request->headers->set('PHP_AUTH_PW', 'bar');

    $response = $this->handleRequestWithConfig($request, array_merge($this->getProtectedConfig(), [
      'authentication_type' => Configuration::AUTH_DRUPAL,
    ]));

    $this->assertEmpty($response);
  }

  /**
   * Test access denied when protected with Drupal as identity provider with invalid credentials.
   */
  public function testAccessDeniedWhenProtectedFromDrupalInvalidCredentials() {
    $request = new Request();
    $request->headers->set('PHP_AUTH_USER', 'foo');
    $request->headers->set('PHP_AUTH_PW', 'ber');

    $response = $this->handleRequestWithConfig($request, array_merge($this->getProtectedConfig(), [
      'authentication_type' => Configuration::AUTH_DRUPAL,
    ]));

    $this->assertProtected($response);
  }

  /**
   * Test access denied when protected with Drupal as identity provider with an unknown user.
   */
  public function testAccessDeniedWhenProtectedFromDrupalUnknownUser() {
    $request = new Request();
    $request->headers->set('PHP_AUTH_USER', 'unknown');
    $request->headers->set('PHP_AUTH_PW', 'bar');

    $response = $this->handleRequestWithConfig($request, array_merge($this->getProtectedConfig(), [
      'authentication_type' => Configuration::AUTH_DRUPAL,
    ]));

    $this->assertProtected($response);
  }

  /**
   * Test access granted when not protected by environment key.
   */
  public function testAccessGrantedWhenNotProtectedByEnvironment() {
    $request = new Request();
    $response = $this->handleRequestWithConfig($request, array_merge($this->getProtectedConfig(), [
      'protect' => Configuration::PROTECT_ENV_ENABLED
    ]));

    $this->assertEmpty($response);
  }

  /**
   * Test access denied when protected by environment key.
   */
  public function testAccessDeniedWhenProtectedByEnvironment() {
    putenv('PBF_ENABLED=1');

    $request = new Request();
    $response = $this->handleRequestWithConfig($request, array_merge($this->getProtectedConfig(), [
      'protect' => Configuration::PROTECT_ENV_ENABLED,
      'environment_key' => 'PBF_ENABLED'
    ]));

    $this->assertProtected($response);
  }

  /**
   * Test access granted when protected by environment key with valid credentials.
   */
  public function testAccessGrantedWhenProtectedByEnvironmentWithValidCredentials() {
    putenv('PBF_ENABLED=1');

    $request = new Request();
    $request->headers->set('PHP_AUTH_USER', 'foo');
    $request->headers->set('PHP_AUTH_PW', 'bar');

    $response = $this->handleRequestWithConfig($request, array_merge($this->getProtectedConfig(), [
      'protect' => Configuration::PROTECT_ENV_ENABLED,
      'environment_key' => 'PBF_ENABLED'
    ]));

    $this->assertEmpty($response);
  }

  /**
   * Test access denied when protected by environment key with invalid credentials.
   */
  public function testAccessDeniedWhenProtectedByEnvironmentWithInvalidCredentials() {
    putenv('PBF_ENABLED=1');

    $request = new Request();
    $request->headers->set('PHP_AUTH_USER', 'foo');
    $request->headers->set('PHP_AUTH_PW', 'ber');

    $response = $this->handleRequestWithConfig($request, array_merge($this->getProtectedConfig(), [
      'protect' => Configuration::PROTECT_ENV_ENABLED,
      'environment_key' => 'PBF_ENABLED'
    ]));

    $this->assertProtected($response);
  }

  /**
   * Test access denied when protected by environment key with value.
   */
  public function testAccessDeniedWhenProtectedByEnvironmentWithValue() {
    putenv('PBF_PROTECT=ENABLED');

    $request = new Request();
    $response = $this->handleRequestWithConfig($request, array_merge($this->getProtectedConfig(), [
      'protect' => Configuration::PROTECT_ENV_ENABLED,
      'environment_key' => 'PBF_PROTECT',
      'environment_value' => 'ENABLED'
    ]));

    $this->assertProtected($response);
  }

  /**
   * Test access granted when protected by environment key with value.
   */
  public function testAccessGrantedWhenProtectedByEnvironmentWithValue() {
    putenv('PBF_PROTECT=DISABLED');

    $request = new Request();
    $response = $this->handleRequestWithConfig($request, array_merge($this->getProtectedConfig(), [
      'protect' => Configuration::PROTECT_ENV_ENABLED,
      'environment_key' => 'PBF_PROTECT',
      'environment_value' => 'ENABLED'
    ]));

    $this->assertEmpty($response);
  }

  /**
   * Assert unauthorized response.
   *
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   Symfony Response.
   */
  private function assertProtected(Response $response) {
    $this->assertEquals($response->getStatusCode(), Response::HTTP_UNAUTHORIZED);
    $this->assertEquals($response->getContent(), 'Access Denied');
    $this->assertContains('Secured Area', $response->headers->get('www-authenticate'));
    $this->assertContains('no-cache', $response->headers->get('cache-control'));
  }

  /**
   * Get default config which enables protection.
   *
   * @return array
   *   Configuration
   */
  private function getProtectedConfig() {
    return [
      'protect' => Configuration::PROTECT_ENABLED,
      'content' => 'Access Denied',
      'realm' => 'Secured Area'
    ];
  }

  /**
   * Handle request with specified config.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request.
   * @param array $config
   *   Configuration.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Response.
   */
  private function handleRequestWithConfig(Request $request, array $config) {
    $configuration = new Configuration($this->getConfigFactoryStub([
      'protect_before_launch.settings' => $config,
    ]));

    $user = $this->createMock(UserInterface::class);
    $user->method('getPassword')->willReturn('bar');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')->with($this->logicalOr(
      $this->equalTo(['name' => 'foo']),
      $this->equalTo(['name' => 'unknown'])
    ))->will($this->returnCallback(function ($value) use ($user) {
      switch ($value['name']) {
        case 'foo':
          return [$user];

        default:
          return [];
      }
    }));

    $entityTypeManager = $this->createMock(EntityTypeManager::class);
    $entityTypeManager->method('getStorage')->willReturn($storage);

    $killSwitch = $this->createMock(KillSwitch::class);
    $password = $this->createMock(PasswordInterface::class);
    $password->method('check')->with($this->logicalOr(
      $this->equalTo('bar'),
      $this->equalTo('ber')
    ))->will($this->returnCallback(function ($value) {
      return $value === 'bar';
    }));

    $httpKernel = $this->createMock(HttpKernelInterface::class);

    $protectKernel = new ProtectHttpKernel($httpKernel, $configuration, $entityTypeManager, $killSwitch, $password);
    $response = $protectKernel->handle($request);

    return $response;
  }

}
