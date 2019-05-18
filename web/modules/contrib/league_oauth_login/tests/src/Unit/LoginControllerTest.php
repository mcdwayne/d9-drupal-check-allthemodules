<?php

namespace Drupal\league_oauth_login\Unit;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\externalauth\ExternalAuthInterface;
use Drupal\league_oauth_login\Controller\LoginController;
use Drupal\league_oauth_login\LeagueOauthLoginInterface;
use Drupal\league_oauth_login\LeagueOauthLoginPluginManager;
use Drupal\user\UserDataInterface;
use Drupal\user\UserStorageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class LoginControllerTest.
 *
 * @group league_oauth_login
 */
class LoginControllerTest extends \PHPUnit_Framework_TestCase {

  /**
   * Mock login manager.
   *
   * @var \Drupal\league_oauth_login\LeagueOauthLoginPluginManager
   */
  private $loginManager;

  /**
   * Get it.
   *
   * @return \Drupal\league_oauth_login\LeagueOauthLoginPluginManager
   *   The manager.
   */
  public function getLoginManager() {
    return $this->loginManager;
  }

  /**
   * Set it.
   *
   * @param \Drupal\league_oauth_login\LeagueOauthLoginPluginManager $loginManager
   *   The manager.
   */
  public function setLoginManager(LeagueOauthLoginPluginManager $loginManager) {
    $this->loginManager = $loginManager;
  }

  /**
   * Test that we get a 404 when a login provider does not exist.
   */
  public function testNonExistingPlugin() {
    $mock_login = $this->createMock(LeagueOauthLoginPluginManager::class);
    $exception = new PluginNotFoundException('bogus_id');
    $mock_login->expects($this->once())
      ->method('createInstance')
      ->willThrowException($exception);
    $this->setLoginManager($mock_login);
    $controller = $this->getController();
    $request = new Request();
    $this->expectException(NotFoundHttpException::class);
    $controller->login($request, 'bogus_id');
  }

  /**
   * Test how we create a user data key.
   */
  public function testCreateUserDataKey() {
    $mock_plugin = $this->createMock(LeagueOauthLoginInterface::class);
    $mock_plugin->expects($this->once())
      ->method('getPluginId')
      ->willReturn('test_id');
    $this->assertEquals('test_id.token', LoginController::createUserDataKey($mock_plugin));
  }

  /**
   * Helper.
   */
  private function getController() {
    $mock_user_storage = $this->createMock(UserStorageInterface::class);
    $mock_logger = $this->createMock(LoggerInterface::class);
    $mock_config = $this->createMock(ConfigFactoryInterface::class);
    $mock_user_data = $this->createMock(UserDataInterface::class);
    $mock_login_manager = $this->getLoginManager();
    $mock_event = $this->createMock(EventDispatcherInterface::class);
    $mock_session = $this->createMock(SessionInterface::class);
    $mock_switch = $this->createMock(KillSwitch::class);
    $mock_auth = $this->createMock(ExternalAuthInterface::class);
    $mock_match = $this->createMock(CurrentRouteMatch::class);
    return new LoginController($mock_user_storage, $mock_logger, $mock_config, $mock_user_data, $mock_login_manager, $mock_event, $mock_session, $mock_switch, $mock_auth, $mock_match);
  }

}
