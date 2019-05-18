<?php
/**
 * Created by PhpStorm.
 * User: th140
 * Date: 12/1/17
 * Time: 3:44 PM
 */

namespace Drupal\Tests\basicshib\Kernel;


use Drupal\basicshib\AuthenticationHandlerInterface;
use Drupal\basicshib\SessionTracker;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\basicshib\Traits\MockTrait;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

class AuthFilterPluginImplementationTest extends KernelTestBase {
  use MockTrait;

  public static $modules = ['basicshib', 'basicshib_test', 'user', 'system'];

  public function setUp() {
    parent::setUp();
    $this->installConfig(['basicshib', 'basicshib_test']);
    $this->installEntitySchema('user');
    $this->installSchema('system', 'sequences');

    /** @var Config $config */
    $config = $this->container->get('config.factory')
      ->getEditable('basicshib.settings');

    $plugins = $config->get('plugins');

    $plugins['auth_filter'] = ['basicshib_test'];

    $config->set('plugins', $plugins);
    $config->save();

  }

  private function setConfig(array $auth_filter) {
    /** @var Config $config */
    $config = $this->container->get('config.factory')
      ->getEditable('basicshib_test.settings');


    $default = $config->get('auth_filter');
    $config->set('auth_filter',  $auth_filter + $default);
    $config->save();
  }

  /**
   * Test clearing session when user is anonymous.
   */
  public function testClearSessionWithPluginDenied() {
    $this->setConfig([
      'check_session_return_value' =>
        AuthenticationHandlerInterface::AUTHCHECK_REVOKED_BY_PLUGIN
    ]);

    $request = new Request();
    $request->setSession(new Session());
    $request->getSession()
      ->set(SessionTracker::VARNAME, '1234');

    $request->server->set('Shib_Session_ID', '1234');

    $request_stack = new RequestStack();
    $request_stack->push($request);

    $this->container->set('request_stack', $request_stack);
    /** @var AuthenticationHandlerInterface $handler */
    $handler = $this->container->get('basicshib.authentication_handler');

    /** @var UserInterface $account */
    $account = $this->container->get('entity_type.manager')
      ->getStorage('user')
      ->create(['name' => 'test', 'mail' => 'test', 'status' => 1]);
    $account->save();

    user_login_finalize($account);

    $proxy = new AccountProxy();
    $proxy->setAccount($account);

    self::assertTrue($proxy->isAuthenticated());
    self::assertTrue($request->getSession()->has(SessionTracker::VARNAME));

    $result = $handler->checkUserSession($request, $proxy);
    self::assertEquals($result, AuthenticationHandlerInterface::AUTHCHECK_REVOKED_BY_PLUGIN);
  }

}
