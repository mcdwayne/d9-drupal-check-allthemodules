<?php

namespace Drupal\Tests\open_connect\Kernel;

use Drupal\Core\Routing\LocalRedirectResponse;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\open_connect\Controller\RedirectController;
use Drupal\open_connect\UncacheableTrustedRedirectResponse;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class OpenConnectTest extends EntityKernelTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'open_connect',
    'open_connect_test',
  ];

  /**
   * The plugin manager.
   *
   * @var \Drupal\open_connect\Plugin\OpenConnect\ProviderManagerInterface
   */
  protected $pluginManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The http kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The CSRF token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfToken;

  /**
   * The session.
   *
   * @var \Symfony\Component\HttpFoundation\Session\Session
   */
  protected $session;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('user', 'users_data');
    $this->installEntitySchema('open_connect');
    $this->installConfig('open_connect');

    $this->pluginManager = $this->container->get('plugin.manager.open_connect.provider');
    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->httpKernel = $this->container->get('http_kernel');
    $this->currentUser = $this->container->get('current_user');
    $this->csrfToken = $this->container->get('csrf_token');
    $this->session = $this->container->get('session');

    $this->createUser(); // A dummy user with uid 1.

    $this->config('open_connect.settings')
      ->set('providers', [
        'test_provider' => [
          'mode' => 'live',
          'client_id' => 'test_client_id',
          'client_secret' => 'test_client_secret',
          'scope' => 'test scope',
        ],
        'test_provider2' => [
          'mode' => 'live',
          'client_id' => 'test_client_id2',
          'client_secret' => 'test_client_secret2',
          'scope' => 'test scope2',
        ],
        'test_provider3' => [
          'mode' => 'live',
          'client_id' => 'test_client_id3',
          'client_secret' => 'test_client_secret3',
          'scope' => 'test scope3',
        ],
      ])
      ->save();
  }

  /**
   * Tests provider assertConfiguration().
   *
   * @expectedException \InvalidArgumentException
   * @expectedExceptionMessage Client secret is not set.
   */
  public function testProviderAssertConfiguration() {
    /** @var \Drupal\open_connect\Plugin\OpenConnect\Provider\ProviderInterface $provider */
    $provider = $this->pluginManager->createInstance('test_provider', [
      'client_id' => $this->randomMachineName(),
    ]);
    $provider->getAuthorizeUrl('test_state');
  }

  /**
   * Tests provider getAuthorizeUrl().
   */
  public function testProviderGetAuthorizeUrl() {
    /** @var \Drupal\open_connect\Plugin\OpenConnect\Provider\ProviderInterface $provider */
    $provider = $this->pluginManager->createInstance('test_provider', [
      'mode' => 'live',
      'client_id' => 'test_client_id',
      'client_secret' => 'test_client_secret',
      'scope' => 'test scope',
    ]);
    $expected = 'https://test.example.com/oauth2/authorize'
      . '?response_type=code'
      . '&client_id=test_client_id'
      . "&redirect_uri={$this->getRedirectUri('test_provider')}"
      . '&state=test_state'
      . '&scope=test%20scope';
    $this->assertEquals($expected, $provider->getAuthorizeUrl('test_state')->toString(), 'Authorization url is returned.');

    // WeChat MP has strict parameter order.
    $provider = $this->pluginManager->createInstance('wechat_mp', [
      'mode' => 'live',
      'client_id' => 'test_client_id',
      'client_secret' => 'test_client_secret',
      'scope' => 'test scope',
    ]);
    $expected = 'https://open.weixin.qq.com/connect/oauth2/authorize'
      . '?appid=test_client_id'
      . "&redirect_uri={$this->getRedirectUri('wechat_mp')}"
      . '&response_type=code'
      . '&scope=test%20scope'
      . '&state=test_state'
      . '#wechat_redirect';
    $this->assertEquals($expected, $provider->getAuthorizeUrl('test_state')->toString(), 'WeChat MP authorization url is correct.');
  }

  /**
   * Tests provider authenticate().
   */
  public function testProviderAuthenticate() {
    // Create a user with the specific name.
    $timestamp = time();
    $name = 'user_' . date('YmdHis', $timestamp);
    $this->createUser(['name' => $name]);

    // Create provider1.
    /** @var \Drupal\open_connect_test\Plugin\OpenConnect\Provider\TestProvider $provider1 */
    $provider1 = $this->pluginManager->createInstance('test_provider', [
      'mode' => 'live',
      'client_id' => 'test_client_id',
      'client_secret' => 'test_client_secret',
      'scope' => 'test scope',
    ]);
    $provider1->fetchTokenResponse = [
      'access_token' => 'test_access_token',
      'expires_in' => 3600,
      'refresh_token' => 'test_refresh_token',
      'openid' => 'test_openid',
      'scope' => 'test scope',
    ];

    // Authenticate by provider1.
    $user1 = $provider1->authenticate('test_code');
    $this->assertFalse($user1->isNew(), 'User is authenticated.');
    $this->assertTrue($user1->isActive(), 'User is active by default.');
    $this->assertTrue(in_array($user1->getAccountName(), [
      $name . '_1',
      // The test may have consumed 1 second.
      'user_' . date('YmdHis', $timestamp + 1),
    ]), 'Unique user name is set.');

    $open_connect_storage = $this->entityTypeManager->getStorage('open_connect');
    $entities = $open_connect_storage->loadMultiple();
    $this->assertEquals(1, count($entities), 'A new open connect entity is created.');

    // Assert open_connect1.
    /** @var \Drupal\open_connect\Entity\OpenConnectInterface $open_connect */
    $open_connect1 = reset($entities);
    $this->assertEquals('test_provider', $open_connect1->getProvider());
    $this->assertEquals('test_openid', $open_connect1->getOpenid());
    $this->assertEmpty($open_connect1->getUnionid());
    $this->assertEquals($user1->id(), $open_connect1->getAccountId());

    // Return same token response with an addition of unionid.
    $provider1->fetchTokenResponse = [
      'access_token' => 'test_access_token',
      'expires_in' => 3600,
      'refresh_token' => 'test_refresh_token',
      'openid' => 'test_openid',
      'unionid' => 'test_unionid',
      'scope' => 'test scope',
    ];

    // Authenticate by provider1 again.
    $temp_user1 = $provider1->authenticate('test_code');
    $this->assertEquals($user1->id(), $temp_user1->id(), 'Same user is returned because of same provider and openid.');
    $this->assertEquals(1, count($open_connect_storage->loadMultiple()), 'No new open connect entity is created.');
    $open_connect1 = $this->reloadEntity($open_connect1);
    $this->assertEquals('test_unionid', $open_connect1->getUnionid(), 'Unionid is set to open_connect1.');

    // Create provider2 with same unionid.
    /** @var \Drupal\open_connect_test\Plugin\OpenConnect\Provider\TestProvider2 $provider2 */
    $provider2 = $this->pluginManager->createInstance('test_provider2', [
      'mode' => 'live',
      'client_id' => 'test_client_id2',
      'client_secret' => 'test_client_secret2',
      'scope' => 'test scope2',
    ]);
    $provider2->fetchTokenResponse = [
      'access_token' => 'test_access_token2',
      'expires_in' => 3600,
      'refresh_token' => 'test_refresh_token2',
      'openid' => 'test_openid2',
      'unionid' => 'test_unionid',
      'scope' => 'test scope2',
    ];

    // Authenticate by provider2.
    $user2 = $provider2->authenticate('test_code');
    $this->assertFalse($user2->isNew(), 'User2 is authenticated.');
    $this->assertTrue($user2->isActive(), 'User2 is active by default.');
    $this->assertEquals($user1->id(), $user2->id(), 'Same user is returned because of same unionid.');

    $open_connect_storage = $this->entityTypeManager->getStorage('open_connect');
    $entities = $open_connect_storage->loadMultiple();
    $this->assertEquals(2, count($entities), '2 open connect entities are created for the same user.');

    // Assert open_connect2.
    /** @var \Drupal\open_connect\Entity\OpenConnectInterface $open_connect */
    $open_connect2 = end($entities);
    $this->assertEquals('test_provider2', $open_connect2->getProvider());
    $this->assertEquals('test_openid2', $open_connect2->getOpenid());
    $this->assertEquals('test_unionid', $open_connect2->getUnionid(), 'Same unionid.');
    $this->assertEquals($user2->id(), $open_connect2->getAccountId());
  }

  /**
   * Tests failing provider.
   *
   * @expectedException \Drupal\open_connect\Exception\OpenConnectException
   * @expectedExceptionMessage 40029: invalid code
   */
  public function testFailingProvider() {
    // Create provider1.
    /** @var \Drupal\open_connect_test\Plugin\OpenConnect\Provider\TestProvider3 $provider */
    $provider = $this->pluginManager->createInstance('test_provider3', [
      'mode' => 'live',
      'client_id' => 'test_client_id',
      'client_secret' => 'test_client_secret',
      'scope' => 'test scope',
    ]);
    $provider->fetchTokenResponse = [
      'errcode' => 40029,
      'errmsg' => 'invalid code',
    ];

    $provider->authenticate('test_code');
  }

  /**
   * Tests controller authorize() with invalid identity provider.
   *
   * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   * @expectedExceptionMessage Invalid identity provider.
   */
  public function testControllerAuthorizeInvalidProvider() {
    // Authorize test_provider.
    $request = Request::create('/open-connect/authorize/invalid_provider');
    $request->setSession($this->session);
    $this->httpKernel->handle($request, HttpKernelInterface::MASTER_REQUEST, FALSE);
  }

  /**
   * Tests controller authorize().
   */
  public function testControllerAuthorize() {
    // Authorize test_provider.
    $request = Request::create('/open-connect/authorize/test_provider');
    $request->setSession($this->session);
    $response = $this->httpKernel->handle($request, HttpKernelInterface::MASTER_REQUEST, FALSE);
    $this->assertTrue($response instanceof UncacheableTrustedRedirectResponse);
    $this->assertStringStartsWith('https://test.example.com/oauth2/authorize?'
      . 'response_type=code&client_id=test_client_id&'
      . 'redirect_uri=http%3A//localhost/open-connect/authenticate/test_provider',
      $response->getTargetUrl()
    );

    // Authorize test_provider2.
    $request = Request::create('/open-connect/authorize/test_provider2');
    $request->setSession($this->session);
    $response = $this->httpKernel->handle($request, HttpKernelInterface::MASTER_REQUEST, FALSE);
    $this->assertTrue($response instanceof UncacheableTrustedRedirectResponse);
    $this->assertStringStartsWith('https://test2.example.com/oauth2/authorize?'
      . 'response_type=code&client_id=test_client_id2&'
      . 'redirect_uri=http%3A//localhost/open-connect/authenticate/test_provider2',
      $response->getTargetUrl()
    );
  }

  /**
   * Tests controller checkAccess() with missing state.
   *
   * @expectedException \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   * @expectedExceptionMessage The 'state' query argument is missing.
   */
  public function testControllerMissingState() {
    $request = Request::create('/open-connect/authenticate/test_provider', 'GET', [
      'code' => 'test_code',
    ]);
    $request->setSession($this->session);
    $this->httpKernel->handle($request, HttpKernelInterface::MASTER_REQUEST, FALSE);
  }

  /**
   * Tests controller checkAccess() with invalid state.
   *
   * @expectedException \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   * @expectedExceptionMessage The 'state' query argument is invalid.
   */
  public function testControllerInvalidState() {
    $request = Request::create('/open-connect/authenticate/test_provider', 'GET', [
      'code' => 'test_code',
      'state' => 'invalid_state',
    ]);
    $request->setSession($this->session);
    $this->httpKernel->handle($request, HttpKernelInterface::MASTER_REQUEST, FALSE);
  }

  /**
   * Tests controller checkAccess() with logged in user.
   *
   * @expectedException \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   * @expectedExceptionMessage Only anonymous user can log in with open connect.
   */
  public function testControllerLoginWithLoggedInUser() {
    $user = $this->createUser();
    $this->currentUser->setAccount($user);

    $request = Request::create('/open-connect/authenticate/test_provider', 'GET', [
      'code' => 'test_code',
      'state' => $this->csrfToken->get(RedirectController::TOKEN_KEY),
    ]);
    $request->setSession($this->session);
    $this->httpKernel->handle($request, HttpKernelInterface::MASTER_REQUEST, FALSE);
  }

  /**
   * Tests controller checkAccess() with anonymous user.
   *
   * @expectedException \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   * @expectedExceptionMessage Ensure the user is logged in.
   */
  public function testControllerConnectWithAnonymousUserUser() {
    $this->session->set('open_connect', ['operation' => 'connect']);

    $request = Request::create('/open-connect/authenticate/test_provider', 'GET', [
      'code' => 'test_code',
      'state' => $this->csrfToken->get(RedirectController::TOKEN_KEY),
    ]);
    $request->setSession($this->session);
    $this->httpKernel->handle($request, HttpKernelInterface::MASTER_REQUEST, FALSE);
  }

  /**
   * Tests controller authenticate() with invalid identity provider.
   *
   * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   * @expectedExceptionMessage Invalid identity provider.
   */
  public function testControllerAuthenticateInvalidProvider() {
    $request = Request::create('/open-connect/authenticate/invalid_provider', 'GET', [
      'code' => 'test_code',
      'state' => $this->csrfToken->get(RedirectController::TOKEN_KEY),
    ]);
    $request->setSession($this->session);
    $this->httpKernel->handle($request, HttpKernelInterface::MASTER_REQUEST, FALSE);
  }

  /**
   * Tests controller authenticate().
   */
  public function testControllerAuthenticate() {
    $this->assertFalse($this->currentUser->isAuthenticated(), 'No user is logged in.');

    $request = Request::create('/open-connect/authenticate/test_provider', 'GET', [
      'code' => 'test_code',
      'state' => $this->csrfToken->get(RedirectController::TOKEN_KEY),
    ]);
    $request->setSession($this->session);
    $response = $this->httpKernel->handle($request, HttpKernelInterface::MASTER_REQUEST, FALSE);
    $this->assertTrue($response instanceof LocalRedirectResponse);
    $this->assertEquals('/user', $response->getTargetUrl(), 'Redirect to the default uri on success.');
    $this->assertTrue($this->currentUser->isAuthenticated(), 'User is logged in.');
    $uid = $this->currentUser->id();

    // Logout.
    $this->currentUser->setAccount(new AnonymousUserSession());

    // Explicitly set return uri.
    $this->session->set('open_connect', [
      'operation' => 'login',
      'return_uri' => '/node/1',
    ]);
    $response = $this->httpKernel->handle($request, HttpKernelInterface::MASTER_REQUEST, FALSE);
    $this->assertTrue($response instanceof LocalRedirectResponse);
    $this->assertEquals('/node/1', $response->getTargetUrl());
    $this->assertTrue($this->currentUser->isAuthenticated(), 'User is logged in.');
    $this->assertEquals($uid, $this->currentUser->id(), 'Same user is logged in.');
  }

  /**
   * Tests controller authenticate() with failure.
   */
  public function testControllerAuthenticateFailure() {
    // The test_provider3 returns a failing token response.
    $request = Request::create('/open-connect/authenticate/test_provider3', 'GET', [
      'code' => 'test_code',
      'state' => $this->csrfToken->get(RedirectController::TOKEN_KEY),
    ]);
    $request->setSession($this->session);
    $response = $this->httpKernel->handle($request, HttpKernelInterface::MASTER_REQUEST, FALSE);
    $this->assertTrue($response instanceof LocalRedirectResponse);
    $this->assertEquals('/', $response->getTargetUrl(), 'Redirect to the default uri on failure.');
    $this->assertFalse($this->currentUser->isAuthenticated(), 'User is not logged in.');
  }

  /**
   * Tests controller blocked user.
   *
   * @expectedException \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   * @expectedExceptionMessage The user is blocked.
   */
  public function testControllerBlockedUser() {
    $this->assertFalse($this->currentUser->isAuthenticated(), 'No user is logged in.');

    $request = Request::create('/open-connect/authenticate/test_provider', 'GET', [
      'code' => 'test_code',
      'state' => $this->csrfToken->get(RedirectController::TOKEN_KEY),
    ]);
    $request->setSession($this->session);
    $this->httpKernel->handle($request, HttpKernelInterface::MASTER_REQUEST, FALSE);
    $this->assertTrue($this->currentUser->isAuthenticated(), 'User is logged in.');

    // Block the user.
    $user = User::load($this->currentUser->id());
    $user->block();
    $user->save();

    // Logout.
    $this->currentUser->setAccount(new AnonymousUserSession());

    $this->httpKernel->handle($request, HttpKernelInterface::MASTER_REQUEST, FALSE);
  }

  /**
   * Tests hook_user_delete().
   */
  public function testUserDelete() {
    $user = $this->createUser();
    $open_connect_storage = $this->entityTypeManager->getStorage('open_connect');

    // Create 2 open connect entities.
    $open_connect_storage->create([
      'provider' => 'test_provider',
      'openid' => 'test_openid',
      'uid' => $user->id(),
    ])->save();
    $open_connect_storage->create([
      'provider' => 'test_provider2',
      'openid' => 'test_openid2',
      'uid' => $user->id(),
    ])->save();

    $entities = $open_connect_storage->loadMultiple();
    $this->assertEquals(2, count($entities), '2 open connect entities are created for the user.');

    $user->delete();
    $entities = $open_connect_storage->loadMultiple();
    $this->assertEquals(0, count($entities), 'All open connect entities of the user are deleted.');
  }

  /**
   * Gets a redirect uri for the given provider.
   *
   * @param string $provider
   *   The provider plugin ID.
   *
   * @return string
   *   The redirect uri.
   */
  private function getRedirectUri($provider) {
    // For str_replace, see UrlHelper::buildQuery().
    return str_replace('%2F', '/', urlencode('http://localhost/open-connect/authenticate/' . $provider));
  }

}
