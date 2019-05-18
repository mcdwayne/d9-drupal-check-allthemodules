<?php
/**
 * Created by PhpStorm.
 * User: th140
 * Date: 11/17/17
 * Time: 8:23 AM
 */

namespace Drupal\Tests\basicshib\Traits;


use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\user\UserInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

trait MockTrait {
  /**
   * @param array $settings
   * @return \PHPUnit_Framework_MockObject_MockObject|ConfigFactoryInterface
   */
  public function getMockConfigFactory(array $settings = []) {

    $settings += [
      'basicshib.settings' => [],
      'basicshib.auth_filter' => [],
    ];

    $settings['basicshib.settings'] += [
      'attribute_map' => [
        'key' => [
          'session_id' => 'Shib_Session_ID',
          'name' => 'eppn',
          'mail' => 'mail',
        ],
        'optional' => [
          ['id' => 'opt1', 'name' => 'OPT1'],
        ],
      ],
      'handlers' => [
        'login' => '/Shibboleth.sso/Login',
        'logout' => '/Shibboleth.sso/Logout',
      ],
      'authentication_plugin' => 'basicshib',
      'generic_login_error_message' => 'generic_login_error',
      'account_blocked_message' => 'user_blocked',
      'default_post_login_redirect_path' => '/user',
    ];

    $settings['basicshib.auth_filter'] += [
      'create' => [
        'allow' => 'false',
        'error' => 'user_creation_not_allowed',
      ],
    ];

    $config_factory_map = [];
    foreach (array_keys($settings) as $config_source) {
      $config = $this->getMockBuilder(ImmutableConfig::class)
        ->disableOriginalConstructor()
        ->setMethods(['get'])
        ->getMock();

      $config_map = [];

      foreach ($settings[$config_source] as $config_name => $config_value) {
        $config_map[] = [$config_name, $config_value];
      }

      $config->method('get')
        ->willReturnMap($config_map);

      $config_factory_map[] = [$config_source, $config];
    }

    $config_factory = $this->getMockForAbstractClass(ConfigFactoryInterface::class);
    $config_factory->method('get')
      ->willReturnMap($config_factory_map);

    return $config_factory;
  }

  public function getMockUser(array $properties) {
    $user = $this->getMockForAbstractClass(UserInterface::class);

    if (isset($properties['name'])) {
      $user->method('getAccountName')
        ->willReturn($properties['name']);
    }

    if (isset($properties['mail'])) {
      $user->method('getEmail')
        ->willReturn($properties['mail']);
    }

    $user->method('isBlocked')
      ->willReturn(empty($properties['status']));

    return $user;
  }

  /**
   * @param UserInterface[] $existing_users
   *
   * @return UserStorageInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  public function getMockUserStorage(array $existing_users = []) {
    $existing_map = [];

    foreach ($existing_users as $user) {
      $existing_map[$user->getAccountName()] = $user;
    }

    $storage = $this->getMockForAbstractClass(UserStorageInterface::class);
    $storage->method('loadByProperties')
      ->willReturnCallback(function (array $properties) use ($existing_map) {
        if (isset($existing_map[$properties['name']])) {
          return [$existing_map[$properties['name']]];
        }
        return [];
      });

    $storage->method('create')
      ->willReturnCallback(function (array $properties) {
        return $this->getMockUser($properties);
      });

    return $storage;
  }

  /**
   * @param array $values
   * @return \PHPUnit_Framework_MockObject_MockObject|SessionInterface
   */
  public function getMockSession(array $values = []) {
    $session = $this->getMockForAbstractClass(SessionInterface::class);
    $session->method('get')
      ->willReturnCallback(function ($name, $default = null) use ($values) {
        return isset($values[$name])
          ? $values[$name]
          : $default;
      });
    return $session;
  }


  public function getMockRequestStack(array $server = [], $session = null) {
    $request_stack = new RequestStack();
    $request = new Request([], [], [], [], [], $server);
    if ($session === null) {
      $request->setSession($this->getMockSession());
    }
    $request_stack->push($request);
    return $request_stack;
  }
}
