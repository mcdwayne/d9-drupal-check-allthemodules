<?php

namespace Drupal\cmlexchange\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Entity\EntityManager;

/**
 * CommerceML CheckAuth service.
 */
class CheckAuth implements CheckAuthInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Debug Service.
   *
   * @var \Drupal\cmlexchange\Servic\DebugServiceInterface
   */
  protected $debugService;

  /**
   * The EntityManager.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * The RequestStack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new CheckAuth object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\cmlexchange\Service\DebugServiceInterface $debug
   *   The debug service.
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   Entity Manager service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Request stack service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    DebugServiceInterface $debug,
    EntityManager $entity_manager,
    RequestStack $request_stack
  ) {
    $this->configFactory = $config_factory;
    $this->debugService = $debug;
    $this->entityManager = $entity_manager;
    $this->requestStack = $request_stack;
  }

  /**
   * MODE checkauth.
   */
  public function modeCheckAuth($type) {

    $result = "failure\n";
    $result .= "auth error\n";

    $login = $this->auth();
    if ($login) {
      $cml = $this->cmlCreate($type, $login);
      if ($cml) {
        $uuid = $cml->uuid->value;
        $result = "success\n";
        $result .= "catalog\n";
        $result .= "{$uuid}\n";
      }
      else {
        $result = "failure\n";
        $result .= "internal error\n";
        $this->debugService->debug(__FUNCTION__, "Ошибка, не создался CmlEntity");
      }
    }
    else {
      $this->debugService->debug(__FUNCTION__, "Ошибка авторизации. Base");
    }

    $this->debugService->debug(__CLASS__, $result);
    return $result;
  }

  /**
   * State.
   */
  public function state() {

    $state = [
      'cml' => FALSE,
      'status' => FALSE,
      'message' => "failure\nauth error\n",
    ];
    if ($this->auth()) {
      if ($cml = $this->check()) {
        $state = [
          'cml' => $cml,
          'status' => TRUE,
          'message' => "ok",
        ];
      }
      else {
        $this->debugService->debug(__CLASS__, "Ошибка авторизации. Cookie.");
      }
    }
    else {
      $this->debugService->debug(__CLASS__, "Ошибка авторизации. Base.");
    }
    return $state;
  }

  /**
   * Проверка авторизации по куке.
   */
  private function check() {

    $result = 0;
    if ($this->auth()) {
      if ($cookie = $this->requestStack->getCurrentRequest()->cookies->get('catalog')) {
        $cml = $this->entityManager->loadEntityByUuid('cml', $cookie);
        if ($cml) {
          return $cml;
        }
        else {
          $this->debugService->debug(__CLASS__, "FAIL: loadEntityByUuid {$cookie}");
        }
      }
      else {
        $this->debugService->debug(__CLASS__, "кука не установлена");
      }
    }
    else {
      $result .= "failure\n";
      $result .= "auth error\n";
      $this->debugService->debug(__CLASS__, "Ошибка авторизации. Base");
    }

    return $result;
  }

  /**
   * Auth.
   */
  private function auth() {
    $config = $this->configFactory->get('cmlexchange.settings');
    $authorized = FALSE;
    if ($config->get('auth')) {
      $authorized = $this->baseAuth();
    }
    else {
      $this->debugService->debug(__CLASS__, "cml_auth = OFF: NO Need authentication");

      $this->debugService->debug(__CLASS__, print_r($this->serverInfo(), TRUE));
      $user = $this->baseAuthUser();
      if ($user) {
        $authorized = $user['name'];
        $this->debugService->debug(__CLASS__, "1C user:" . $authorized);
      }
      else {
        $authorized = TRUE;
        $this->debugService->debug(__CLASS__, "1C user NOT SET");
      }
    }
    return $authorized;
  }

  /**
   * Base Auth User.
   */
  private function serverInfo() {
    $server = [];
    $ops = [
      'HTTP_COOKIE',
      'HTTP_USER_AGENT',
      'HTTP_AUTHORIZATION',
      'REMOTE_ADDR',
      'PHP_AUTH_USER',
      'PHP_AUTH_PW',
    ];
    foreach ($ops as $op) {
      if (isset($_SERVER[$op])) {
        $server[$op] = $_SERVER[$op];
      }
      else {
        $server[$op] = 'MISS';
      }
    }
    return $server;
  }

  /**
   * Base Auth User.
   */
  private function baseAuthUser() {
    $user = FALSE;
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
      $auth = array();
      if (preg_match('/Basic\s+(.*)$/i', $_SERVER['HTTP_AUTHORIZATION'], $auth)) {
        list($auth_name, $auth_pass) = explode(':', base64_decode($auth[1]));
        $user = [
          'name' => $auth_name,
          'pass' => $auth_pass,
        ];
      }
    }
    return $user;
  }

  /**
   * Базовая HTTP Авторизация.
   *
   * #masdzen 20120705.
   * RewriteCond %{REQUEST_URI} !cron.php
   * RewriteCond %{HTTP:Authorization} ^Basic.*
   * RewriteRule (.*) index.php?Authorization=%{HTTP:Authorization} [QSA,L].
   */
  private function baseAuth() {
    $get = print_r($_GET, TRUE);
    $post = print_r($_POST, TRUE);
    $config = $this->configFactory->get('cmlexchange.settings');
    if ($config->get('auth')) {
      $authorized = FALSE;
      $auth = $this->baseAuthUser();
      if ($auth) {
        $config_name = $config->get('auth-user');
        $config_pass = $config->get('auth-pass');
        if (($auth['name'] == $config_name) & ($auth['pass'] == $config_pass)) {
          $authorized = $auth['name'];
        }
        else {
          $this->debugService->debug(__FUNCTION__,
              t('@login:@pass - wrong login pair', ['@login' => $auth['name'], '@pass' => $auth['pass']]));
          $this->debugService->debug(__FUNCTION__,
              t("base authorized :\nget = @get\npost = @post", ['@get' => $get, '@post' => $post]));
        }
      }
      return $authorized;
    }
    else {
      return TRUE;
    }
  }

  /**
   * Создать новую куку.
   */
  private function cmlCreate($type, $login) {
    $route = \Drupal::routeMatch()->getRouteName();
    $storage = $this->entityManager->getStorage('cml');
    $cml = $storage->create([
      'name' => "$type - " . format_date(time(), "custom", 'd.m.Y H:i:s'),
      'login' => $login,
      'type' => $type,
      'ip' => $this->requestStack->getCurrentRequest()->getClientIp(),
      'full' => $route == 'cmlexchange.full' ? TRUE : FALSE,
    ]);
    $cml->save();
    return $cml;
  }

}
